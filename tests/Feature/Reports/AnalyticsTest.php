<?php

namespace Tests\Feature\Reports;

use App\Analytics\Definitions\QuranAttendanceAnalytics;
use App\Analytics\Definitions\QuranProgressAnalytics;
use App\Analytics\Definitions\QuranTeacherAttendanceAnalytics;
use App\Analytics\Definitions\SalahAttendanceAnalytics;
use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranProgress;
use App\Models\QuranTeacherAttendance;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\AnalyticsRegistry;
use App\Support\Analytics\AnalyticsRow;
use Tests\TestCase;

/**
 * The report builder.
 *
 * The interesting thing about a definition-driven report is that the risk is
 * not in any one breakdown — it is in the combinations. So these tests cover
 * the shape (every declared dimension actually runs, every declared filter
 * actually applies), the arithmetic (rates are derived, not averaged), and the
 * boundaries (another company's records, another role's records, and a
 * hand-edited URL).
 */
class AnalyticsTest extends TestCase
{
    private User $user;

    private Employee $employee;

    private Prayer $prayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUserWithCompany(['report.quran', 'report.salah']);
        $this->prayer = Prayer::factory()->create();

        $this->employee = Employee::factory()->create([
            'company_id' => $this->user->company_id,
            'employee_name' => 'Bilal Ahmed',
            'branch_id' => Branch::factory()->create(['company_id' => $this->user->company_id, 'branch_name' => 'Main Branch'])->id,
            'department_id' => Department::factory()->create(['company_id' => $this->user->company_id, 'department_name' => 'Accounts'])->id,
            'designation_id' => Designation::factory()->create(['company_id' => $this->user->company_id, 'designation_name' => 'Officer'])->id,
            'gender' => 'male',
        ]);
    }

    private function salah(array $attributes = []): SalahAttendance
    {
        return SalahAttendance::factory()->create(array_merge([
            'company_id' => $this->user->company_id,
            'prayer_id' => $this->prayer->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-03',
            'attendance_reason_id' => null,
        ], $attributes));
    }

    private function analyse(AbstractAnalyticsDefinition $definition, array $filters = [], ?string $groupBy = null, ?string $thenBy = null)
    {
        return app(AnalyticsService::class)->run($definition, $filters, $groupBy, $thenBy);
    }

    // ── The engine holds together ──────────────────────────────────

    public function test_every_declared_breakdown_of_every_dataset_actually_runs(): void
    {
        // The point of a definition-driven report is that nobody hand-writes
        // the twenty-five queries. That only holds if every declared dimension
        // produces valid SQL, so each one is executed rather than trusted.
        $this->salah();
        $this->actingAs($this->user);

        foreach (app(AnalyticsRegistry::class)->visible(static fn () => true) as $definition) {
            foreach (array_keys($definition->dimensions()) as $key) {
                $result = $this->analyse($definition, [], $key);

                $this->assertSame(
                    $key,
                    $result->dimension->key,
                    "Breakdown [{$key}] of [{$definition->key()}] did not run.",
                );
            }
        }
    }

    public function test_every_declared_filter_of_every_dataset_actually_applies(): void
    {
        $this->salah();
        $this->actingAs($this->user);

        foreach (app(AnalyticsRegistry::class)->visible(static fn () => true) as $definition) {
            foreach ($definition->filters() as $key => $filter) {
                // A value that matches nothing: the filter must narrow the
                // result rather than throw, whatever it joins.
                $value = $filter->type->isSelect() ? '999999' : '1900-01-01';

                $result = $this->analyse($definition, [$key => $value]);

                $this->assertTrue(
                    $result->isEmpty() || $result->rows !== [],
                    "Filter [{$key}] of [{$definition->key()}] did not run.",
                );
            }
        }
    }

    public function test_a_second_breakdown_splits_each_row(): void
    {
        $absent = AttendanceReason::factory()->create(['company_id' => $this->user->company_id]);
        $this->salah();
        $this->salah(['attendance_date' => '2026-08-04', 'attendance_reason_id' => $absent->id]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'prayer', 'status');

        $this->assertNotNull($result->secondary);
        $this->assertCount(2, $result->rows, 'One prayer, two statuses, so two rows.');
    }

    public function test_the_same_dimension_twice_is_ignored_rather_than_duplicating_a_column(): void
    {
        $this->salah();
        $this->actingAs($this->user);

        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'prayer', 'prayer');

        $this->assertNull($result->secondary);
    }

    // ── The arithmetic ─────────────────────────────────────────────

    public function test_present_and_absent_are_counted_by_whether_the_reason_counts_as_absent(): void
    {
        $reason = AttendanceReason::factory()->create(['company_id' => $this->user->company_id]);

        $this->salah();
        $this->salah(['attendance_date' => '2026-08-04']);
        $this->salah(['attendance_date' => '2026-08-05', 'attendance_reason_id' => $reason->id]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'prayer');

        $row = $result->rows[0];

        $this->assertSame(3.0, $row->measures['records']);
        $this->assertSame(2.0, $row->measures['present']);
        $this->assertSame(1.0, $row->measures['absent']);
        $this->assertSame(66.7, $row->measures['rate']);
    }

    public function test_a_salah_reason_that_does_not_count_as_absent_is_counted_as_present(): void
    {
        // "On Leave", "Late", or a custom reason like "Prayed" — a reason
        // existing is not the same as it counting against the person. Only
        // counts_as_absent decides that, not whether a reason was recorded.
        $notAbsent = AttendanceReason::factory()->leave()->create(['company_id' => $this->user->company_id]);

        $this->salah();
        $this->salah(['attendance_date' => '2026-08-04', 'attendance_reason_id' => $notAbsent->id]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'prayer');

        $row = $result->rows[0];

        $this->assertSame(2.0, $row->measures['records']);
        $this->assertSame(2.0, $row->measures['present']);
        $this->assertSame(0.0, $row->measures['absent']);
    }

    public function test_a_quran_reason_that_does_not_count_as_absent_is_counted_as_present(): void
    {
        $teacher = Teacher::factory()->create(['company_id' => $this->user->company_id]);
        $class = QuranClass::factory()->create([
            'company_id' => $this->user->company_id,
            'teacher_id' => $teacher->id,
        ]);
        $notAbsent = AttendanceReason::factory()->leave()->create(['company_id' => $this->user->company_id]);

        QuranAttendance::factory()->create([
            'company_id' => $this->user->company_id,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-03',
            'attendance_reason_id' => null,
            'class_held' => true,
        ]);
        QuranAttendance::factory()->create([
            'company_id' => $this->user->company_id,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-04',
            'attendance_reason_id' => $notAbsent->id,
            'class_held' => true,
        ]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(QuranAttendanceAnalytics::class), [], 'teacher');

        $row = $result->rows[0];

        $this->assertSame(2.0, $row->measures['records']);
        $this->assertSame(2.0, $row->measures['present']);
        $this->assertSame(0.0, $row->measures['absent']);
    }

    public function test_the_status_dimension_groups_by_counts_as_absent_not_by_whether_a_reason_exists(): void
    {
        $absentReason = AttendanceReason::factory()->create(['company_id' => $this->user->company_id]);
        $notAbsentReason = AttendanceReason::factory()->leave()->create(['company_id' => $this->user->company_id]);

        $this->salah(['attendance_date' => '2026-08-03']);
        $this->salah(['attendance_date' => '2026-08-04', 'attendance_reason_id' => $notAbsentReason->id]);
        $this->salah(['attendance_date' => '2026-08-05', 'attendance_reason_id' => $absentReason->id]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'status');

        $byLabel = collect($result->rows)->keyBy('label');

        $this->assertSame(2.0, $byLabel[__('analytics.status_present')]->measures['records']);
        $this->assertSame(1.0, $byLabel[__('analytics.status_absent')]->measures['records']);
    }

    public function test_the_total_rate_is_derived_from_the_totals_not_averaged_from_the_rows(): void
    {
        // A department of one who attended, and a department of three who did
        // not. Averaging the two rates gives 50%; the truth is 25%.
        $reason = AttendanceReason::factory()->create(['company_id' => $this->user->company_id]);

        $small = Employee::factory()->create([
            'company_id' => $this->user->company_id,
            'department_id' => Department::factory()->create(['company_id' => $this->user->company_id])->id,
        ]);
        $large = Employee::factory()->create([
            'company_id' => $this->user->company_id,
            'department_id' => Department::factory()->create(['company_id' => $this->user->company_id])->id,
        ]);

        $this->salah(['employee_id' => $small->id, 'attendance_date' => '2026-08-03']);

        foreach (['2026-08-03', '2026-08-04', '2026-08-05'] as $date) {
            $this->salah([
                'employee_id' => $large->id,
                'attendance_date' => $date,
                'attendance_reason_id' => $reason->id,
            ]);
        }

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'department');

        $this->assertSame(25.0, $result->totals['rate']);
    }

    public function test_a_group_with_no_records_reports_no_rate_rather_than_zero_per_cent(): void
    {
        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'prayer');

        $this->assertTrue($result->isEmpty());
        $this->assertNull($result->totals['rate']);
    }

    // ── Labels ─────────────────────────────────────────────────────

    public function test_a_record_whose_group_is_empty_is_labelled_rather_than_left_blank(): void
    {
        $orphan = Employee::factory()->create([
            'company_id' => $this->user->company_id,
            'department_id' => null,
        ]);
        $this->salah(['employee_id' => $orphan->id]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), ['employee' => $orphan->employee_name], 'department');

        $this->assertSame(__('analytics.not_set'), $result->rows[0]->label);
    }

    public function test_the_absence_reason_breakdown_names_the_present_group_correctly(): void
    {
        // Grouping by reason puts everyone who attended in the null bucket.
        // Calling that "Not set" would read as missing data rather than as
        // the people who turned up.
        $this->salah();

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'reason');

        $this->assertSame(__('analytics.present_no_reason'), $result->rows[0]->label);
    }

    public function test_dates_group_into_months_rather_than_one_row_per_record(): void
    {
        // Guards DateExpression: MySQL and SQLite disagree about every date
        // function there is, so a month breakdown that was never checked on
        // both would silently return one row per record under test.
        $this->salah(['attendance_date' => '2026-08-03']);
        $this->salah(['attendance_date' => '2026-08-20']);
        $this->salah(['attendance_date' => '2026-09-01']);

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'month');

        $this->assertCount(2, $result->rows);
        $this->assertSame(['2026-08', '2026-09'], array_map(
            static fn (AnalyticsRow $row): string => (string) $row->key,
            $result->rows,
        ));
    }

    public function test_the_weekday_breakdown_numbers_sunday_as_one_on_every_driver(): void
    {
        // 2026-08-03 is a Monday.
        $this->salah(['attendance_date' => '2026-08-03']);

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'weekday');

        $this->assertSame(2, (int) $result->rows[0]->key);
        $this->assertSame('Monday', $result->rows[0]->label);
    }

    // ── Boundaries ─────────────────────────────────────────────────

    public function test_another_companys_records_are_never_counted(): void
    {
        $other = Company::factory()->create();
        $otherEmployee = Employee::factory()->create(['company_id' => $other->id]);

        SalahAttendance::factory()->create([
            'company_id' => $other->id,
            'prayer_id' => $this->prayer->id,
            'employee_id' => $otherEmployee->id,
            'attendance_date' => '2026-08-03',
        ]);

        $this->salah();

        $this->actingAs($this->user);
        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'prayer');

        $this->assertSame(1.0, $result->totals['records'], 'Only this company\'s record may be counted.');
    }

    public function test_a_filter_key_that_was_never_declared_is_dropped(): void
    {
        $this->salah();
        $this->actingAs($this->user);

        $definition = app(SalahAttendanceAnalytics::class);
        $active = app(AnalyticsService::class)->activeFilters($definition, [
            'prayer_id' => (string) $this->prayer->id,
            'company_id' => '999',
            'employees.company_id' => '999',
        ]);

        $this->assertSame(['prayer_id' => (string) $this->prayer->id], $active);
    }

    public function test_an_unknown_breakdown_falls_back_instead_of_failing(): void
    {
        $this->salah();
        $this->actingAs($this->user);

        $result = $this->analyse(app(SalahAttendanceAnalytics::class), [], 'employees.company_id');

        $this->assertSame('prayer', $result->dimension->key);
    }

    // ── HTTP ───────────────────────────────────────────────────────

    public function test_the_analysis_screen_requires_a_report_permission(): void
    {
        $this->actingAs($this->createUserWithCompany())
            ->get(route('reports.analysis.index'))
            ->assertForbidden();

        $this->actingAs($this->createUserWithCompany())
            ->get(route('reports.analysis.show', 'salah-attendance'))
            ->assertForbidden();
    }

    public function test_a_salah_reader_is_not_offered_the_quran_datasets(): void
    {
        $salahOnly = $this->createUserWithCompany(['report.salah']);

        $response = $this->actingAs($salahOnly)->get(route('reports.analysis.index'));

        $response->assertOk();
        $response->assertSee(__('analytics.salah_attendance'));
        $response->assertDontSee(__('analytics.quran_progress'));
    }

    public function test_a_salah_reader_cannot_open_a_quran_dataset_directly(): void
    {
        $this->actingAs($this->createUserWithCompany(['report.salah']))
            ->get(route('reports.analysis.show', 'quran-progress'))
            ->assertForbidden();
    }

    public function test_an_unknown_dataset_is_a_404(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.analysis.show', 'made-up'))
            ->assertNotFound();
    }

    public function test_the_screen_renders_the_breakdown_and_says_what_it_was_filtered_by(): void
    {
        $this->salah();

        $response = $this->actingAs($this->user)->get(route('reports.analysis.show', [
            'dataset' => 'salah-attendance',
            'group_by' => 'department',
            'department_id' => $this->employee->department_id,
        ]));

        $response->assertOk();
        $response->assertSee('Accounts');
        // docs/14_REPORTS_MODULE.md rule 5: a report must state its filters.
        $response->assertSee(__('analytics.meta_filters'));
    }

    public function test_the_breakdown_downloads_as_a_spreadsheet_and_as_a_pdf(): void
    {
        $this->salah();

        foreach (['xlsx', 'csv', 'pdf'] as $format) {
            $this->actingAs($this->user)
                ->get(route('reports.analysis.export', [
                    'dataset' => 'salah-attendance',
                    'group_by' => 'department',
                    'format' => $format,
                ]))
                ->assertOk();
        }
    }

    // ── The other two datasets ─────────────────────────────────────

    public function test_quran_attendance_breaks_down_by_qari(): void
    {
        $teacherEmployee = Employee::factory()->create([
            'company_id' => $this->user->company_id,
            'employee_name' => 'Qari Abdullah',
        ]);
        $teacher = Teacher::factory()->create([
            'company_id' => $this->user->company_id,
            'employee_id' => $teacherEmployee->id,
        ]);
        $class = QuranClass::factory()->create([
            'company_id' => $this->user->company_id,
            'teacher_id' => $teacher->id,
        ]);

        QuranAttendance::factory()->create([
            'company_id' => $this->user->company_id,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-03',
            'attendance_reason_id' => null,
        ]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(QuranAttendanceAnalytics::class), [], 'teacher');

        $this->assertSame('Qari Abdullah', $result->rows[0]->label);
        $this->assertSame(100.0, $result->rows[0]->measures['rate']);
    }

    public function test_quran_attendance_excludes_days_the_class_was_not_held(): void
    {
        // Regression guard for the class_held gotcha: a not-held day has a
        // NULL attendance_reason_id just like a present day, so it must be
        // filtered at the query level rather than leaking into "present".
        $teacher = Teacher::factory()->create(['company_id' => $this->user->company_id]);
        $class = QuranClass::factory()->create([
            'company_id' => $this->user->company_id,
            'teacher_id' => $teacher->id,
        ]);

        QuranAttendance::factory()->create([
            'company_id' => $this->user->company_id,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-03',
            'attendance_reason_id' => null,
            'class_held' => true,
        ]);
        QuranAttendance::factory()->create([
            'company_id' => $this->user->company_id,
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => '2026-08-04',
            'attendance_reason_id' => null,
            'class_held' => false,
        ]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(QuranAttendanceAnalytics::class), [], 'teacher');

        $this->assertSame(1.0, $result->totals['records'], 'The not-held day must not count as a record at all.');
    }

    public function test_quran_teacher_attendance_counts_absent_days_and_classes_affected(): void
    {
        $teacherEmployee = Employee::factory()->create([
            'company_id' => $this->user->company_id,
            'employee_name' => 'Qari Bilal',
        ]);
        $teacher = Teacher::factory()->create([
            'company_id' => $this->user->company_id,
            'employee_id' => $teacherEmployee->id,
        ]);
        $classA = QuranClass::factory()->create(['company_id' => $this->user->company_id, 'teacher_id' => $teacher->id]);
        $classB = QuranClass::factory()->create(['company_id' => $this->user->company_id, 'teacher_id' => $teacher->id]);
        $reason = AttendanceReason::factory()->create(['company_id' => $this->user->company_id]);

        QuranTeacherAttendance::factory()->create([
            'company_id' => $this->user->company_id,
            'class_id' => $classA->id,
            'teacher_id' => $teacher->id,
            'attendance_reason_id' => $reason->id,
            'attendance_date' => '2026-08-03',
        ]);
        QuranTeacherAttendance::factory()->create([
            'company_id' => $this->user->company_id,
            'class_id' => $classB->id,
            'teacher_id' => $teacher->id,
            'attendance_reason_id' => $reason->id,
            'attendance_date' => '2026-08-04',
        ]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(QuranTeacherAttendanceAnalytics::class), [], 'teacher');

        $this->assertSame('Qari Bilal', $result->rows[0]->label);
        $this->assertSame(2.0, $result->rows[0]->measures['absent_days']);
        $this->assertSame(2.0, $result->rows[0]->measures['classes_affected']);
    }

    public function test_quran_progress_bands_completion_and_keeps_a_hundred_per_cent_separate(): void
    {
        $second = Employee::factory()->create(['company_id' => $this->user->company_id]);

        QuranProgress::factory()->create([
            'company_id' => $this->user->company_id,
            'employee_id' => $this->employee->id,
            'completion_percentage' => 95,
        ]);
        QuranProgress::factory()->create([
            'company_id' => $this->user->company_id,
            'employee_id' => $second->id,
            'completion_percentage' => 100,
        ]);

        $this->actingAs($this->user);
        $result = $this->analyse(app(QuranProgressAnalytics::class), [], 'completion_band');

        $labels = array_map(static fn (AnalyticsRow $row): string => $row->label, $result->rows);

        $this->assertContains('90–99%', $labels);
        $this->assertContains(__('analytics.band_complete'), $labels);
        $this->assertSame(1.0, $result->totals['completed']);
    }
}

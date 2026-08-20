<?php

namespace App\Analytics\Definitions;

use App\Analytics\Concerns\DescribesAttendance;
use App\Analytics\Concerns\DescribesEmployees;
use App\Enums\AttendanceReasonType;
use App\Models\Branch;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Support\Analytics\AbstractAnalyticsDefinition;
use App\Support\Analytics\Dimension;
use App\Support\Analytics\Filter;
use App\Support\Analytics\Join;
use App\Support\Analytics\Measure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

/**
 * Quran attendance, sliced any way it can be sliced.
 *
 * The record knows the class and the qari who took it; the student brings their
 * branch, department, designation and Quran standing. So "qari sahab ke aetbar
 * se", "class ke hisaab se", "department ke hisaab se" and "mahine ke hisaab
 * se" are all the same report with a different breakdown chosen.
 *
 * A teacher is an employee wearing a second hat, so their name lives one table
 * further out than it looks — teachers → employees, joined under its own alias
 * so it cannot collide with the student's row.
 */
class QuranAttendanceAnalytics extends AbstractAnalyticsDefinition
{
    use DescribesAttendance, DescribesEmployees;

    private const TABLE = 'quran_attendance';

    public function key(): string
    {
        return 'quran-attendance';
    }

    public function label(): string
    {
        return __('analytics.quran_attendance');
    }

    public function description(): string
    {
        return __('analytics.quran_attendance_description');
    }

    public function permission(): string
    {
        return 'report.quran';
    }

    public function table(): string
    {
        return self::TABLE;
    }

    /**
     * A day the class was not held is not an attendance event — present or
     * absent — so it is excluded here rather than folded into either measure.
     * Teacher-side absence is its own dataset (QuranTeacherAttendanceAnalytics).
     */
    public function query(): Builder
    {
        return QuranAttendance::query()->where('class_held', true);
    }

    public function defaultDimension(): string
    {
        return 'teacher';
    }

    public function drillDown(): ?array
    {
        return [
            'route' => 'reports.quran-attendance',
            'filters' => [
                'class_id' => 'class_id',
                'teacher_id' => 'teacher_id',
                'date_from' => 'date_from',
                'date_to' => 'date_to',
            ],
        ];
    }

    protected function joinList(): array
    {
        return array_merge(
            $this->employeeJoins(self::TABLE),
            $this->reasonJoin(self::TABLE),
            $this->recorderJoin(self::TABLE),
            [
                Join::make('quran_classes', 'quran_classes', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.class_id', '=', 'quran_classes.id')),

                // Where the class is held, which is not always where the
                // student is posted.
                Join::make(
                    'class_branches',
                    'branches',
                    fn (JoinClause $join) => $join
                        ->on('quran_classes.branch_id', '=', 'class_branches.id'),
                    ['quran_classes'],
                    'class_branches'
                ),

                Join::make('teachers', 'teachers', fn (JoinClause $join) => $join
                    ->on(self::TABLE.'.teacher_id', '=', 'teachers.id')),

                // The qari's own name lives on their employee record.
                Join::make(
                    'teacher_employees',
                    'employees',
                    fn (JoinClause $join) => $join
                        ->on('teachers.employee_id', '=', 'teacher_employees.id'),
                    ['teachers'],
                    'teacher_employees'
                ),
            ],
        );
    }

    protected function dimensionList(): array
    {
        $quran = __('analytics.group_quran');

        return array_merge(
            [
                Dimension::lookup(
                    key: 'teacher',
                    label: __('analytics.dim_teacher'),
                    keyColumn: self::TABLE.'.teacher_id',
                    labelColumn: 'teacher_employees.employee_name',
                    joins: ['teacher_employees'],
                    drillFilter: 'teacher_id',
                    group: $quran,
                ),
                Dimension::lookup(
                    key: 'class',
                    label: __('analytics.dim_class'),
                    keyColumn: self::TABLE.'.class_id',
                    labelColumn: 'quran_classes.class_name',
                    joins: ['quran_classes'],
                    drillFilter: 'class_id',
                    group: $quran,
                ),
                Dimension::lookup(
                    key: 'class_branch',
                    label: __('analytics.dim_class_branch'),
                    keyColumn: 'quran_classes.branch_id',
                    labelColumn: 'class_branches.branch_name',
                    joins: ['class_branches'],
                    group: $quran,
                ),
            ],
            $this->attendanceDimensions(self::TABLE),
            $this->employeeDimensions(),
        );
    }

    protected function filterList(): array
    {
        $quran = __('analytics.group_quran');

        return array_merge(
            $this->attendanceFilters(self::TABLE, AttendanceReasonType::Quran),
            [
                Filter::select(
                    key: 'teacher_id',
                    label: __('analytics.dim_teacher'),
                    options: static fn (): array => self::teacherOptions(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.teacher_id', $value),
                    group: $quran,
                ),
                Filter::select(
                    key: 'class_id',
                    label: __('analytics.dim_class'),
                    options: static fn (): array => QuranClass::query()->orderBy('class_name')->pluck('class_name', 'id')->all(),
                    apply: fn (Builder $query, string $value) => $query->where(self::TABLE.'.class_id', $value),
                    group: $quran,
                ),
                Filter::select(
                    key: 'class_branch_id',
                    label: __('analytics.dim_class_branch'),
                    options: static fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all(),
                    apply: fn (Builder $query, string $value) => $query->where('quran_classes.branch_id', $value),
                    joins: ['quran_classes'],
                    group: $quran,
                ),
            ],
            $this->employeeFilters(),
        );
    }

    /** @return list<Measure> */
    public function measures(): array
    {
        return $this->attendanceMeasures(self::TABLE);
    }

    /**
     * Qaris by name rather than by teacher code — the code means nothing to
     * whoever is reading the report.
     *
     * @return array<int, string>
     */
    private static function teacherOptions(): array
    {
        return Teacher::query()
            ->join('employees', 'teachers.employee_id', '=', 'employees.id')
            ->orderBy('employees.employee_name')
            ->pluck('employees.employee_name', 'teachers.id')
            ->all();
    }
}

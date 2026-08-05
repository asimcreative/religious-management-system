<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\AttendanceReason;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatMember;
use App\Models\Language;
use App\Models\Prayer;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Models\QuranDepartment;
use App\Models\QuranProgress;
use App\Models\QuranStatus;
use App\Models\SalahAttendance;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic demo data for screenshots and presentations.
 * Idempotent — safe to run multiple times.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('company_code', 'DEMO')->firstOrFail();
        $companyId = $company->id;

        // ── Master Data ───────────────────────────────────────────────
        $branches = $this->seedBranches($companyId);
        $departments = $this->seedDepartments($companyId);
        $designations = $this->seedDesignations($companyId);
        $this->seedLanguages($companyId);
        $quranDepts = $this->seedQuranDepartments($companyId);
        $quranStatuses = $this->seedQuranStatuses($companyId);
        $attendanceReasons = $this->seedAttendanceReasons($companyId);
        $prayers = Prayer::all();

        // ── Employees ────────────────────────────────────────────────
        $employees = $this->seedEmployees($companyId, $branches, $departments, $designations);

        // ── Teachers ─────────────────────────────────────────────────
        $teachers = $this->seedTeachers($companyId, $employees, $branches);

        // ── Quran Classes ─────────────────────────────────────────────
        $classes = $this->seedQuranClasses($companyId, $teachers);

        // ── Jamaats ───────────────────────────────────────────────────
        $jamaats = $this->seedJamaats($companyId, $employees);

        // ── Quran Progress ────────────────────────────────────────────
        $this->seedQuranProgress($companyId, $employees, $classes, $quranStatuses);

        // ── Attendance ────────────────────────────────────────────────
        $this->seedSalahAttendance($companyId, $employees, $prayers);
        $this->seedQuranAttendance($companyId, $employees, $classes);

        $this->command->info('Demo data seeded successfully.');
    }

    private function seedBranches(int $companyId): array
    {
        $data = [
            ['branch_name' => 'Main Masjid Branch', 'address' => 'Block A, Model Town, Lahore', 'phone' => '+92-42-35761234', 'status' => Status::Active],
            ['branch_name' => 'North Campus Branch', 'address' => 'Johar Town, Lahore', 'phone' => '+92-42-35882000', 'status' => Status::Active],
            ['branch_name' => 'South Branch', 'address' => 'DHA Phase 5, Lahore', 'phone' => '+92-42-35761111', 'status' => Status::Active],
        ];

        $result = [];
        foreach ($data as $row) {
            $result[] = Branch::updateOrCreate(
                ['company_id' => $companyId, 'branch_name' => $row['branch_name']],
                array_merge($row, ['company_id' => $companyId])
            );
        }

        return $result;
    }

    private function seedDepartments(int $companyId): array
    {
        $names = ['Administration', 'Finance', 'Education', 'Maintenance', 'Security'];
        $result = [];
        foreach ($names as $name) {
            $result[] = Department::updateOrCreate(
                ['company_id' => $companyId, 'department_name' => $name],
                ['company_id' => $companyId, 'department_name' => $name, 'status' => Status::Active]
            );
        }

        return $result;
    }

    private function seedDesignations(int $companyId): array
    {
        $names = ['Imam', 'Muezzin', 'Quran Teacher', 'Administrator', 'Accountant', 'Caretaker', 'Security Guard'];
        $result = [];
        foreach ($names as $name) {
            $result[] = Designation::updateOrCreate(
                ['company_id' => $companyId, 'designation_name' => $name],
                ['company_id' => $companyId, 'designation_name' => $name, 'status' => Status::Active]
            );
        }

        return $result;
    }

    private function seedLanguages(int $companyId): void
    {
        $langs = [
            ['language_name' => 'Arabic', 'locale' => 'ar', 'direction' => 'rtl'],
            ['language_name' => 'Urdu', 'locale' => 'ur', 'direction' => 'rtl'],
            ['language_name' => 'English', 'locale' => 'en', 'direction' => 'ltr'],
        ];
        foreach ($langs as $lang) {
            Language::updateOrCreate(
                ['company_id' => $companyId, 'locale' => $lang['locale']],
                array_merge($lang, ['company_id' => $companyId, 'status' => Status::Active])
            );
        }
    }

    private function seedQuranDepartments(int $companyId): array
    {
        $names = ['Hifz Department', 'Nazira Department', 'Tajweed Department'];
        $result = [];
        foreach ($names as $name) {
            $result[] = QuranDepartment::updateOrCreate(
                ['company_id' => $companyId, 'department_name' => $name],
                ['company_id' => $companyId, 'department_name' => $name, 'status' => Status::Active]
            );
        }

        return $result;
    }

    private function seedAttendanceReasons(int $companyId): array
    {
        $reasons = [
            ['reason_name' => 'Absent',     'color' => '#dc3545', 'icon' => 'bi-x-circle',    'counts_as_absent' => true,  'counts_as_leave' => false],
            ['reason_name' => 'Late',       'color' => '#fd7e14', 'icon' => 'bi-clock',        'counts_as_absent' => false, 'counts_as_leave' => false],
            ['reason_name' => 'On Leave',   'color' => '#0dcaf0', 'icon' => 'bi-calendar-x',  'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Sick',       'color' => '#6f42c1', 'icon' => 'bi-bandaid',      'counts_as_absent' => false, 'counts_as_leave' => true],
            ['reason_name' => 'Travelling', 'color' => '#20c997', 'icon' => 'bi-airplane',     'counts_as_absent' => false, 'counts_as_leave' => true],
        ];

        $result = [];
        foreach ($reasons as $reason) {
            $result[] = AttendanceReason::updateOrCreate(
                ['company_id' => $companyId, 'reason_name' => $reason['reason_name']],
                array_merge($reason, ['company_id' => $companyId, 'status' => Status::Active])
            );
        }

        return $result;
    }

    private function seedQuranStatuses(int $companyId): array
    {
        $statuses = ['Active', 'Completed', 'On Hold', 'Withdrawn'];
        $result = [];
        foreach ($statuses as $status) {
            $result[] = QuranStatus::updateOrCreate(
                ['company_id' => $companyId, 'status_name' => $status],
                ['company_id' => $companyId, 'status_name' => $status]
            );
        }

        return $result;
    }

    private function seedEmployees(int $companyId, array $branches, array $departments, array $designations): array
    {
        $employeeData = [
            ['code' => 'EMP-0001', 'name' => 'Muhammad Ali Khan', 'mobile' => '+92-300-1111001', 'gender' => 'male', 'dob' => '1985-03-15', 'email' => 'malik@demo.test'],
            ['code' => 'EMP-0002', 'name' => 'Ahmad Hassan Sheikh', 'mobile' => '+92-300-1111002', 'gender' => 'male', 'dob' => '1990-07-22', 'email' => 'ahsheikh@demo.test'],
            ['code' => 'EMP-0003', 'name' => 'Tariq Mehmood', 'mobile' => '+92-300-1111003', 'gender' => 'male', 'dob' => '1988-11-10', 'email' => 'tmehmood@demo.test'],
            ['code' => 'EMP-0004', 'name' => 'Ibrahim Siddiqui', 'mobile' => '+92-300-1111004', 'gender' => 'male', 'dob' => '1992-05-28', 'email' => 'isiddiqui@demo.test'],
            ['code' => 'EMP-0005', 'name' => 'Yasir Ahmed', 'mobile' => '+92-300-1111005', 'gender' => 'male', 'dob' => '1995-01-14', 'email' => 'yahmed@demo.test'],
            ['code' => 'EMP-0006', 'name' => 'Bilal Akhtar', 'mobile' => '+92-300-1111006', 'gender' => 'male', 'dob' => '1987-09-03', 'email' => 'bakhtar@demo.test'],
            ['code' => 'EMP-0007', 'name' => 'Usman Farooq', 'mobile' => '+92-300-1111007', 'gender' => 'male', 'dob' => '1993-12-20', 'email' => 'ufarooq@demo.test'],
            ['code' => 'EMP-0008', 'name' => 'Khalid Mahmood', 'mobile' => '+92-300-1111008', 'gender' => 'male', 'dob' => '1986-06-17', 'email' => 'kmahmood@demo.test'],
            ['code' => 'EMP-0009', 'name' => 'Fahad Nawaz', 'mobile' => '+92-300-1111009', 'gender' => 'male', 'dob' => '1991-04-08', 'email' => 'fnawaz@demo.test'],
            ['code' => 'EMP-0010', 'name' => 'Shahid Hussain', 'mobile' => '+92-300-1111010', 'gender' => 'male', 'dob' => '1984-08-25', 'email' => 'shussain@demo.test'],
            ['code' => 'EMP-0011', 'name' => 'Zubair Rashid', 'mobile' => '+92-300-1111011', 'gender' => 'male', 'dob' => '1994-02-11', 'email' => 'zrashid@demo.test'],
            ['code' => 'EMP-0012', 'name' => 'Hamza Qureshi', 'mobile' => '+92-300-1111012', 'gender' => 'male', 'dob' => '1989-10-30', 'email' => 'hqureshi@demo.test'],
        ];

        $result = [];
        $branchIds = array_column($branches, null, 'id');
        $branchList = array_values($branchIds);
        $deptList = array_values($departments);
        $desgList = array_values($designations);

        foreach ($employeeData as $i => $data) {
            $result[] = Employee::updateOrCreate(
                ['company_id' => $companyId, 'employee_code' => $data['code']],
                [
                    'company_id' => $companyId,
                    'employee_code' => $data['code'],
                    'employee_name' => $data['name'],
                    'mobile' => $data['mobile'],
                    'email' => $data['email'],
                    'dob' => $data['dob'],
                    'gender' => $data['gender'],
                    'employment_status' => Status::Active,
                    'branch_id' => $branchList[$i % count($branchList)]->id,
                    'department_id' => $deptList[$i % count($deptList)]->id,
                    'designation_id' => $desgList[$i % count($desgList)]->id,
                ]
            );
        }

        return $result;
    }

    private function seedTeachers(int $companyId, array $employees, array $branches): array
    {
        $result = [];
        $teacherEmployees = array_slice($employees, 0, 4);
        $branchList = array_values($branches);

        foreach ($teacherEmployees as $i => $employee) {
            $code = 'TCH-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $teacher = Teacher::updateOrCreate(
                ['company_id' => $companyId, 'teacher_code' => $code],
                [
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'teacher_code' => $code,
                    'status' => Status::Active,
                    'notes' => 'Certified Quran teacher with '.(5 + $i * 2).' years experience.',
                ]
            );

            // Attach branch
            $teacher->branches()->syncWithoutDetaching([$branchList[$i % count($branchList)]->id]);
            $result[] = $teacher;
        }

        return $result;
    }

    private function seedQuranClasses(int $companyId, array $teachers): array
    {
        $classData = [
            ['name' => 'Hifz Morning Class', 'code' => 'CLS-0001', 'start' => '06:00:00', 'end' => '08:00:00', 'max' => 20],
            ['name' => 'Nazira Evening Class', 'code' => 'CLS-0002', 'start' => '17:00:00', 'end' => '19:00:00', 'max' => 25],
            ['name' => 'Tajweed Weekend Class', 'code' => 'CLS-0003', 'start' => '09:00:00', 'end' => '11:00:00', 'max' => 30],
            ['name' => 'Adults Quran Class', 'code' => 'CLS-0004', 'start' => '20:00:00', 'end' => '21:30:00', 'max' => 15],
        ];

        $result = [];
        foreach ($classData as $i => $data) {
            $teacher = $teachers[$i % count($teachers)] ?? null;

            $result[] = QuranClass::updateOrCreate(
                ['company_id' => $companyId, 'class_code' => $data['code']],
                [
                    'company_id' => $companyId,
                    'class_name' => $data['name'],
                    'class_code' => $data['code'],
                    'teacher_id' => $teacher?->id,
                    'start_time' => $data['start'],
                    'end_time' => $data['end'],
                    'max_strength' => $data['max'],
                    'status' => Status::Active,
                ]
            );
        }

        return $result;
    }

    private function seedJamaats(int $companyId, array $employees): array
    {
        $jamaatData = [
            ['number' => 'JMT-0001', 'name' => 'Jamaat Al-Fajr'],
            ['number' => 'JMT-0002', 'name' => 'Jamaat Al-Dhuhr'],
            ['number' => 'JMT-0003', 'name' => 'Jamaat Al-Asr'],
        ];

        $result = [];
        foreach ($jamaatData as $i => $data) {
            $jamaat = Jamaat::updateOrCreate(
                ['company_id' => $companyId, 'jamaat_number' => $data['number']],
                [
                    'company_id' => $companyId,
                    'jamaat_number' => $data['number'],
                    'jamaat_name' => $data['name'],
                    'status' => Status::Active,
                ]
            );

            // Attach members
            $memberSlice = array_slice($employees, $i * 3, 4);
            foreach ($memberSlice as $employee) {
                JamaatMember::updateOrCreate(
                    ['jamaat_id' => $jamaat->id, 'employee_id' => $employee->id],
                    ['jamaat_id' => $jamaat->id, 'employee_id' => $employee->id, 'is_active' => true, 'joined_at' => now()->subMonths(rand(1, 6))->format('Y-m-d')]
                );
            }

            $result[] = $jamaat;
        }

        return $result;
    }

    private function seedQuranProgress(int $companyId, array $employees, array $classes, array $statuses): void
    {
        $surahs = ['Al-Fatiha', 'Al-Baqarah', 'Al-Imran', 'An-Nisa', 'Al-Maidah', 'Al-Anam'];

        foreach ($employees as $i => $employee) {
            $class = $classes[$i % count($classes)];
            $status = $statuses[0]; // Active

            // Enroll in class
            QuranClassMember::updateOrCreate(
                ['class_id' => $class->id, 'employee_id' => $employee->id],
                ['class_id' => $class->id, 'employee_id' => $employee->id, 'is_active' => true, 'joined_at' => now()->subMonths(rand(1, 6))->format('Y-m-d')]
            );

            // Create progress record
            QuranProgress::updateOrCreate(
                ['company_id' => $companyId, 'employee_id' => $employee->id],
                [
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'quran_status_id' => $status->id,
                    'current_lesson' => 'Lesson '.(($i * 3) + 1),
                    'current_surah' => $surahs[$i % count($surahs)],
                    'current_sipara' => ($i % 30) + 1,
                    'current_page' => ($i * 20) + 10,
                    'completion_percentage' => round(($i / count($employees)) * 100, 2),
                ]
            );
        }
    }

    private function seedSalahAttendance(int $companyId, array $employees, $prayers): void
    {
        if ($prayers->isEmpty()) {
            return;
        }

        $dates = [];
        for ($d = 7; $d >= 1; $d--) {
            $dates[] = Carbon::now()->subDays($d)->format('Y-m-d');
        }

        foreach ($dates as $date) {
            foreach ($prayers as $prayer) {
                // Mark 70-85% of employees as present
                $presentEmployees = array_slice($employees, 0, (int) (count($employees) * 0.8));
                foreach ($presentEmployees as $employee) {
                    SalahAttendance::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'attendance_date' => $date,
                            'prayer_id' => $prayer->id,
                            'employee_id' => $employee->id,
                        ],
                        [
                            'company_id' => $companyId,
                            'attendance_date' => $date,
                            'prayer_id' => $prayer->id,
                            'employee_id' => $employee->id,
                        ]
                    );
                }
            }
        }
    }

    private function seedQuranAttendance(int $companyId, array $employees, array $classes): void
    {
        $dates = [];
        for ($d = 7; $d >= 1; $d--) {
            $dates[] = Carbon::now()->subDays($d)->format('Y-m-d');
        }

        foreach ($dates as $date) {
            foreach ($classes as $class) {
                $presentEmployees = array_slice($employees, 0, (int) (count($employees) * 0.75));
                foreach ($presentEmployees as $employee) {
                    QuranAttendance::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'attendance_date' => $date,
                            'class_id' => $class->id,
                            'employee_id' => $employee->id,
                        ],
                        [
                            'company_id' => $companyId,
                            'attendance_date' => $date,
                            'class_id' => $class->id,
                            'employee_id' => $employee->id,
                        ]
                    );
                }
            }
        }
    }
}

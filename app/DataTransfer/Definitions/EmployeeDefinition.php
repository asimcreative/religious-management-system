<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\QuranDepartment;
use App\Models\QuranStatus;
use App\Services\EmployeeService;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Illuminate\Database\Eloquent\Model;

class EmployeeDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'employees';
    }

    public function modelClass(): string
    {
        return Employee::class;
    }

    public function label(): string
    {
        return __('employees.employees');
    }

    public function singularLabel(): string
    {
        return __('employees.employee');
    }

    public function icon(): string
    {
        return 'bi-people';
    }

    public function indexRoute(): string
    {
        return 'employees.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'employee.view',
            'import' => 'employee.import',
            'export' => 'employee.export',
            'sample' => 'employee.import',
        ];
    }

    /**
     * The photo and user_id columns are deliberately absent. A photo is a
     * binary upload that a spreadsheet cannot carry, and linking an employee
     * to a login account grants access — that decision belongs in the user
     * administration flow, not in a bulk file.
     *
     * @return array<int, Column>
     */
    protected function defineColumns(): array
    {
        return [
            Column::string('employee_code')
                ->label(__('employees.employee_code'))
                ->required()
                ->unique()
                ->rules(['max:50'])
                ->sample('EMP-0001')
                ->width(16),

            Column::string('employee_name')
                ->label(__('employees.employee_name'))
                ->required()
                ->rules(['max:255'])
                ->sample('Muhammad Bilal')
                ->width(26),

            // Stored encrypted with a hashed lookup column; uniqueness is
            // therefore checked against cnic_hash, which HasEncryptedCnic
            // maintains. See ImportUniquenessChecker.
            Column::cnic('cnic')
                ->label(__('employees.cnic'))
                ->uniqueVia('cnic_hash', static fn (string $value): string => hash('sha256', str_replace('-', '', $value)))
                // HasEncryptedCnic strips the dashes before encrypting, so the
                // accessor returns thirteen bare digits. They are re-dashed on
                // the way out, or an exported file would fail the application's
                // own CNIC format rule when imported straight back in.
                ->exportUsing(static fn (Employee $employee): ?string => self::formatCnic($employee->cnic))
                ->sample('35201-1234567-1')
                ->width(18),

            Column::phone('mobile')
                ->label(__('employees.mobile'))
                ->rules(['max:30'])
                ->sample('0300-1234567'),

            Column::email('email')
                ->label(__('employees.email'))
                ->sample('bilal@example.com')
                ->width(24),

            Column::date('dob')
                ->label(__('employees.dob'))
                ->rules(['before:today'])
                ->sample('1990-05-21'),

            Column::choice('gender', ['male' => __('employees.male'), 'female' => __('employees.female')])
                ->label(__('employees.gender'))
                ->sample(__('employees.male')),

            Column::lookup('branch_id', Branch::class, 'branch_name')
                ->label(__('employees.branch'))
                ->required()
                ->sample('Main Branch')
                ->width(20),

            Column::lookup('department_id', Department::class, 'department_name')
                ->label(__('employees.department'))
                ->required()
                ->sample('Administration')
                ->width(20),

            Column::lookup('designation_id', Designation::class, 'designation_name')
                ->label(__('employees.designation'))
                ->required()
                ->sample('Officer')
                ->width(20),

            Column::enum('employment_status', Status::class)
                ->label(__('employees.status'))
                ->required()
                ->only([Status::Active, Status::Inactive, Status::Suspended])
                ->sample('Active'),

            Column::lookup('quran_department_id', QuranDepartment::class, 'department_name')
                ->label(__('employees.quran_department'))
                ->sample('Hifz')
                ->width(20),

            // The relation is named quranStatusRelation on the model, because
            // "status" was already taken by the enum column.
            Column::lookup('quran_status_id', QuranStatus::class, 'status_name')
                ->label(__('employees.quran_status'))
                ->relation('quranStatusRelation')
                ->sample('In Progress')
                ->width(20),

            Column::text('notes')
                ->label(__('employees.notes'))
                ->rules(['max:5000'])
                ->sample('')
                ->width(30),
        ];
    }

    /**
     * An employee with history is not deletable, in bulk or one at a time.
     *
     * EmployeeController refuses this on the single-record path; asking the
     * same service here keeps one rule rather than two that can drift.
     */
    public function canDelete(Model $record): bool
    {
        return app(EmployeeService::class)->canDelete((int) $record->getKey());
    }

    /** Render 13 stored digits as the 00000-0000000-0 form people read. */
    private static function formatCnic(?string $stored): ?string
    {
        if ($stored === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $stored) ?? $stored;

        if (strlen($digits) !== 13) {
            return $stored;
        }

        return substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12, 1);
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['employee_code', 'employee_name', 'mobile', 'email'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return [
            'branch_id' => 'branch_id',
            'department_id' => 'department_id',
            'designation_id' => 'designation_id',
            'employment_status' => 'employment_status',
            'quran_department_id' => 'quran_department_id',
            'quran_status_id' => 'quran_status_id',
        ];
    }

    /**
     * EmployeeRepository::search() orders newest first.
     *
     * @return array<string, string>
     */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}

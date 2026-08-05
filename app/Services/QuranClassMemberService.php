<?php

namespace App\Services;

use App\Helpers\TimezoneHelper;
use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuranClassMemberService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Get active members of a class.
     */
    public function getActiveMembers(int $classId): Collection
    {
        /** @var QuranClass $class */
        $class = QuranClass::findOrFail($classId);

        return $class->activeMembers()
            ->orderBy('employee_name')
            ->get();
    }

    /**
     * Add an employee to a class.
     * Deactivates any existing active membership first (one active class rule).
     */
    public function addMember(int $classId, int $employeeId): void
    {
        DB::transaction(function () use ($classId, $employeeId): void {
            $class = QuranClass::query()->lockForUpdate()->findOrFail($classId);
            $employee = Employee::query()->lockForUpdate()->findOrFail($employeeId);

            $this->ensureSameCompany($class, $employee);
            $today = Carbon::today(TimezoneHelper::getCompanyTimezone((int) $class->company_id));

            $existing = QuranClassMember::query()
                ->where('class_id', $class->id)
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if ((! $existing || ! $existing->is_active) && $class->isFull()) {
                throw ValidationException::withMessages([
                    'employee_id' => __('quran_classes.class_full'),
                ]);
            }

            $activeMemberships = QuranClassMember::query()
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereHas('quranClass', fn ($query) => $query->where('company_id', $class->company_id));

            if ($existing?->is_active) {
                $activeMemberships->whereKeyNot($existing->id);
            }

            $activeMemberships->update([
                'is_active' => false,
                'left_at' => $today,
            ]);

            if ($existing) {
                $oldValues = $this->membershipValues($existing);

                $existing->update([
                    'is_active' => true,
                    'joined_at' => $today,
                    'left_at' => null,
                ]);

                $this->auditMembershipChange($existing, $class, 'updated', $oldValues);

                return;
            }

            $membership = QuranClassMember::create([
                'class_id' => $class->id,
                'employee_id' => $employee->id,
                'is_active' => true,
                'joined_at' => $today,
            ]);

            $this->auditMembershipChange($membership, $class, 'created');
        });
    }

    /**
     * Remove an employee from a class (deactivate, preserve history).
     */
    public function removeMember(int $classId, int $employeeId): void
    {
        DB::transaction(function () use ($classId, $employeeId): void {
            $class = QuranClass::query()->lockForUpdate()->findOrFail($classId);
            $employee = Employee::query()->lockForUpdate()->findOrFail($employeeId);

            $this->ensureSameCompany($class, $employee);
            $today = Carbon::today(TimezoneHelper::getCompanyTimezone((int) $class->company_id));

            $membership = QuranClassMember::query()
                ->where('class_id', $class->id)
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($membership === null) {
                return;
            }

            $oldValues = $this->membershipValues($membership);

            $membership->update([
                'is_active' => false,
                'left_at' => $today,
            ]);

            $this->auditMembershipChange($membership, $class, 'updated', $oldValues);
        });
    }

    private function ensureSameCompany(QuranClass $class, Employee $employee): void
    {
        if ((int) $class->company_id !== (int) $employee->company_id) {
            throw (new ModelNotFoundException)->setModel(Employee::class, [$employee->id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function membershipValues(QuranClassMember $membership): array
    {
        return [
            'class_id' => $membership->class_id,
            'employee_id' => $membership->employee_id,
            'is_active' => $membership->is_active,
            'joined_at' => $this->dateValue($membership->joined_at),
            'left_at' => $this->dateValue($membership->left_at),
        ];
    }

    private function dateValue(CarbonInterface|string|null $value): ?string
    {
        return $value instanceof CarbonInterface ? $value->toDateString() : $value;
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     */
    private function auditMembershipChange(
        QuranClassMember $membership,
        QuranClass $class,
        string $action,
        ?array $oldValues = null,
    ): void {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $this->auditLogService->logModelChange(
            $user,
            $membership,
            (int) $class->company_id,
            $action,
            $oldValues,
            $this->membershipValues($membership),
        );
    }
}

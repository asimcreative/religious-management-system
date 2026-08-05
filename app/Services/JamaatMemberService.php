<?php

namespace App\Services;

use App\Helpers\TimezoneHelper;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\JamaatMember;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JamaatMemberService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Get active members of a Jamaat.
     */
    public function getActiveMembers(int $jamaatId): Collection
    {
        /** @var Jamaat $jamaat */
        $jamaat = Jamaat::findOrFail($jamaatId);

        return JamaatMember::where('jamaat_id', $jamaat->id)
            ->where('is_active', true)
            ->with('employee')
            ->get();
    }

    /**
     * Add an employee to a Jamaat.
     * Deactivates any existing active membership first (one active Jamaat rule).
     */
    public function addMember(int $jamaatId, int $employeeId): void
    {
        DB::transaction(function () use ($jamaatId, $employeeId): void {
            $jamaat = Jamaat::query()->lockForUpdate()->findOrFail($jamaatId);
            $employee = Employee::query()->lockForUpdate()->findOrFail($employeeId);

            $this->ensureSameCompany($jamaat, $employee);
            $today = Carbon::today(TimezoneHelper::getCompanyTimezone((int) $jamaat->company_id));

            $existing = JamaatMember::query()
                ->where('jamaat_id', $jamaat->id)
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            $activeMemberships = JamaatMember::query()
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereHas('jamaat', fn ($query) => $query->where('company_id', $jamaat->company_id));

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

                $this->auditMembershipChange($existing, $jamaat, 'updated', $oldValues);

                return;
            }

            $membership = JamaatMember::create([
                'jamaat_id' => $jamaat->id,
                'employee_id' => $employee->id,
                'is_active' => true,
                'joined_at' => $today,
            ]);

            $this->auditMembershipChange($membership, $jamaat, 'created');
        });
    }

    /**
     * Remove an employee from a Jamaat (deactivate, preserve history).
     */
    public function removeMember(int $jamaatId, int $employeeId): void
    {
        DB::transaction(function () use ($jamaatId, $employeeId): void {
            $jamaat = Jamaat::query()->lockForUpdate()->findOrFail($jamaatId);
            $employee = Employee::query()->lockForUpdate()->findOrFail($employeeId);

            $this->ensureSameCompany($jamaat, $employee);
            $today = Carbon::today(TimezoneHelper::getCompanyTimezone((int) $jamaat->company_id));

            $membership = JamaatMember::query()
                ->where('jamaat_id', $jamaat->id)
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

            $this->auditMembershipChange($membership, $jamaat, 'updated', $oldValues);
        });
    }

    private function ensureSameCompany(Jamaat $jamaat, Employee $employee): void
    {
        if ((int) $jamaat->company_id !== (int) $employee->company_id) {
            throw (new ModelNotFoundException)->setModel(Employee::class, [$employee->id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function membershipValues(JamaatMember $membership): array
    {
        return [
            'jamaat_id' => $membership->jamaat_id,
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
        JamaatMember $membership,
        Jamaat $jamaat,
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
            (int) $jamaat->company_id,
            $action,
            $oldValues,
            $this->membershipValues($membership),
        );
    }
}

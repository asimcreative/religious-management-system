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
use Illuminate\Validation\ValidationException;

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
     *
     * An employee belongs to at most one active jamaat. Someone already in
     * another one is refused rather than quietly moved: a silent move takes a
     * member away from a jamaat whose own screen never mentions it, and its
     * attendance sheet just gets shorter the next morning. Moving someone is
     * remove-then-add — two deliberate acts, both audited.
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

            $this->ensureFreeToJoin($jamaat, $employee);

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
     * Refuse an employee who is already active in a different jamaat.
     *
     * The message names that jamaat, because the person doing the adding is
     * usually not the person who needs to release them.
     */
    private function ensureFreeToJoin(Jamaat $jamaat, Employee $employee): void
    {
        /** @var JamaatMember|null $elsewhere */
        $elsewhere = JamaatMember::query()
            ->with('jamaat')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where('jamaat_id', '!=', $jamaat->id)
            ->whereHas('jamaat', fn ($query) => $query->where('company_id', $jamaat->company_id))
            ->first();

        if ($elsewhere === null) {
            return;
        }

        throw ValidationException::withMessages([
            'employee_id' => __('jamaats.already_in_another_jamaat', [
                'name' => $employee->employee_name,
                'jamaat' => $elsewhere->jamaat?->jamaat_name ?? '',
            ]),
        ]);
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

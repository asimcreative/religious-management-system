<?php

namespace App\Services;

use App\Models\JamaatMember;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class JamaatMemberService
{
    /**
     * Get active members of a Jamaat.
     */
    public function getActiveMembers(int $jamaatId): Collection
    {
        return JamaatMember::where('jamaat_id', $jamaatId)
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
        // Deactivate any existing active membership for this employee
        JamaatMember::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'left_at' => Carbon::today(),
            ]);

        // Check if this employee was previously in this Jamaat
        $existing = JamaatMember::where('jamaat_id', $jamaatId)
            ->where('employee_id', $employeeId)
            ->first();

        if ($existing) {
            // Reactivate the existing record
            $existing->update([
                'is_active' => true,
                'joined_at' => Carbon::today(),
                'left_at' => null,
            ]);
        } else {
            // Create new membership
            JamaatMember::create([
                'jamaat_id' => $jamaatId,
                'employee_id' => $employeeId,
                'is_active' => true,
                'joined_at' => Carbon::today(),
            ]);
        }
    }

    /**
     * Remove an employee from a Jamaat (deactivate, preserve history).
     */
    public function removeMember(int $jamaatId, int $employeeId): void
    {
        JamaatMember::where('jamaat_id', $jamaatId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'left_at' => Carbon::today(),
            ]);
    }
}

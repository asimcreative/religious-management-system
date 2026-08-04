<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\QuranClassMember;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class QuranClassMemberService
{
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
        // Deactivate any existing active membership for this employee
        QuranClassMember::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'left_at' => Carbon::today(),
            ]);

        // Check if this employee was previously in this class
        $existing = QuranClassMember::where('class_id', $classId)
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
            QuranClassMember::create([
                'class_id' => $classId,
                'employee_id' => $employeeId,
                'is_active' => true,
                'joined_at' => Carbon::today(),
            ]);
        }
    }

    /**
     * Remove an employee from a class (deactivate, preserve history).
     */
    public function removeMember(int $classId, int $employeeId): void
    {
        QuranClassMember::where('class_id', $classId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'left_at' => Carbon::today(),
            ]);
    }
}

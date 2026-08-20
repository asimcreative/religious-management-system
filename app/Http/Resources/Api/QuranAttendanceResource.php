<?php

namespace App\Http\Resources\Api;

use App\Models\AttendanceReason;
use App\Models\Employee;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property QuranAttendance $resource
 */
class QuranAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'attendance_date' => $this->resource->getRawOriginal('attendance_date'),
            'is_present' => $this->resource->isPresent(),
            'is_absent' => $this->resource->isAbsent(),
            'remarks' => $this->resource->remarks,
            'quran_class' => $this->whenLoaded('quranClass', function () {
                /** @var QuranClass $cls */
                $cls = $this->resource->quranClass;

                return ['id' => $cls->id, 'name' => $cls->class_name];
            }),
            'employee' => $this->whenLoaded('employee', function () {
                /** @var Employee $emp */
                $emp = $this->resource->employee;

                return ['id' => $emp->id, 'name' => $emp->employee_name];
            }),
            'attendance_reason' => $this->whenLoaded('attendanceReason', function () {
                $reason = $this->resource->attendanceReason;

                return $reason instanceof AttendanceReason ? [
                    'id' => $reason->id,
                    'name' => $reason->reason_name,
                ] : null;
            }),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}

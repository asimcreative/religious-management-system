<?php

namespace App\Http\Resources\Api;

use App\Models\AttendanceReason;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\Prayer;
use App\Models\SalahAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SalahAttendance $resource
 */
class SalahAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'attendance_date' => $this->resource->getRawOriginal('attendance_date'),
            'is_present' => $this->resource->isPresent(),
            'remarks' => $this->resource->remarks,
            'prayer' => $this->whenLoaded('prayer', function () {
                /** @var Prayer $prayer */
                $prayer = $this->resource->prayer;

                return ['id' => $prayer->id, 'name' => $prayer->prayer_name];
            }),
            'jamaat' => $this->whenLoaded('jamaat', function () {
                /** @var Jamaat $jamaat */
                $jamaat = $this->resource->jamaat;

                return ['id' => $jamaat->id, 'name' => $jamaat->jamaat_name];
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

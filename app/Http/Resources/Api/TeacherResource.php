<?php

namespace App\Http\Resources\Api;

use App\Models\Employee;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Teacher $resource
 */
class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'teacher_code' => $this->resource->teacher_code,
            'name' => $this->resource->getEmployeeName(),
            'status' => $this->resource->getRawOriginal('status'),
            'status_label' => $this->resource->isActive() ? 'Active' : 'Inactive',
            'branches' => $this->whenLoaded('branches', function () {
                return $this->resource->branches->map(fn ($b) => [
                    'id' => $b->getKey(),
                    'name' => $b->getAttribute('branch_name'),
                ]);
            }),
            'employee' => $this->whenLoaded('employee', function () {
                /** @var Employee $emp */
                $emp = $this->resource->employee;

                return [
                    'id' => $emp->id,
                    'name' => $emp->employee_name,
                    'mobile' => $emp->mobile,
                    'email' => $emp->email,
                ];
            }),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}

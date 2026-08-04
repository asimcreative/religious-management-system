<?php

namespace App\Http\Resources\Api;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Employee $resource
 */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'employee_code' => $this->resource->employee_code,
            'employee_name' => $this->resource->employee_name,
            'mobile' => $this->resource->mobile,
            'email' => $this->resource->email,
            'gender' => $this->resource->gender,
            'employment_status' => $this->resource->getRawOriginal('employment_status'),
            'employment_status_label' => $this->resource->isActive() ? 'Active' : 'Inactive',
            'branch' => $this->whenLoaded('branch', function () {
                /** @var Branch $branch */
                $branch = $this->resource->branch;

                return ['id' => $branch->id, 'name' => $branch->branch_name];
            }),
            'department' => $this->whenLoaded('department', function () {
                /** @var Department $dept */
                $dept = $this->resource->department;

                return ['id' => $dept->id, 'name' => $dept->department_name];
            }),
            'designation' => $this->whenLoaded('designation', function () {
                /** @var Designation $desig */
                $desig = $this->resource->designation;

                return ['id' => $desig->id, 'name' => $desig->designation_name];
            }),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
            'updated_at' => $this->resource->updated_at?->toDateTimeString(),
        ];
    }
}

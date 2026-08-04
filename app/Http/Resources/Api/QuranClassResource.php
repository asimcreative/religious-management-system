<?php

namespace App\Http\Resources\Api;

use App\Models\Branch;
use App\Models\QuranClass;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property QuranClass $resource
 */
class QuranClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'class_code' => $this->resource->class_code,
            'class_name' => $this->resource->class_name,
            'max_strength' => $this->resource->max_strength,
            'active_members_count' => $this->whenCounted('activeMembers'),
            'status' => $this->resource->getRawOriginal('status'),
            'status_label' => $this->resource->isActive() ? 'Active' : 'Inactive',
            'branch' => $this->whenLoaded('branch', function () {
                /** @var Branch $branch */
                $branch = $this->resource->branch;

                return ['id' => $branch->id, 'name' => $branch->branch_name];
            }),
            'teacher' => $this->whenLoaded('teacher', function () {
                /** @var Teacher $teacher */
                $teacher = $this->resource->teacher;

                return ['id' => $teacher->id, 'name' => $teacher->getEmployeeName()];
            }),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}

<?php

namespace App\Http\Resources\Api;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Jamaat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Jamaat $resource
 */
class JamaatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'jamaat_number' => $this->resource->jamaat_number,
            'jamaat_name' => $this->resource->jamaat_name,
            'active_members_count' => $this->whenCounted('activeMembers'),
            'status' => $this->resource->getRawOriginal('status'),
            'status_label' => $this->resource->isActive() ? 'Active' : 'Inactive',
            'branch' => $this->whenLoaded('branch', function () {
                /** @var Branch $branch */
                $branch = $this->resource->branch;

                return ['id' => $branch->id, 'name' => $branch->branch_name];
            }),
            'leader' => $this->whenLoaded('leader', function () {
                /** @var Employee $leader */
                $leader = $this->resource->leader;

                return ['id' => $leader->id, 'name' => $leader->employee_name];
            }),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}

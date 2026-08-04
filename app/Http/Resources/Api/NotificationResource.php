<?php

namespace App\Http\Resources\Api;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Notification $resource
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'message' => $this->resource->message,
            'type' => $this->resource->type,
            'priority' => $this->resource->priority,
            'is_read' => $this->resource->isRead(),
            'read_at' => $this->resource->getRawOriginal('read_at'),
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}

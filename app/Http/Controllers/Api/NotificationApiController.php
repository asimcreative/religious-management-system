<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends BaseApiController
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    /**
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('notification.view');

        $notifications = $this->service->getForUser(
            $request->user()->id,
            $this->perPage($request, 20)
        );

        return $this->successResponse(
            NotificationResource::collection($notifications)->response()->getData(true)
        );
    }

    /**
     * POST /api/v1/notifications/{id}/read
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $this->authorize('notification.read');

        $success = $this->service->markAsRead($id, $request->user()->id);

        if (! $success) {
            return $this->notFoundResponse('Notification not found');
        }

        return $this->successResponse(null, 'Notification marked as read');
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->authorize('notification.read');

        $count = $this->service->markAllAsRead($request->user()->id);

        return $this->successResponse(['marked' => $count], 'All notifications marked as read');
    }

    /**
     * DELETE /api/v1/notifications/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorize('notification.delete');

        $success = $this->service->delete($id, $request->user()->id);

        if (! $success) {
            return $this->notFoundResponse('Notification not found');
        }

        return $this->noContentResponse();
    }
}

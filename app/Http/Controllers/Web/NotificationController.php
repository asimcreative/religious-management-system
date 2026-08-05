<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function index(): View
    {
        $this->authorize('notification.view');

        $notifications = $this->service->getForUser(Auth::id());
        $unreadCount = $this->service->getUnreadCount(Auth::id());

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(int $id): JsonResponse|RedirectResponse
    {
        $this->authorize('notification.read');

        abort_unless($this->service->markAsRead($id, (int) Auth::id()), 404);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function markAllRead(): JsonResponse|RedirectResponse
    {
        $this->authorize('notification.read');

        $count = $this->service->markAllAsRead(Auth::id());

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'marked' => $count]);
        }

        return back()->with('success', __('notifications.all_marked_read'));
    }

    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $this->authorize('notification.delete');

        abort_unless($this->service->delete($id, (int) Auth::id()), 404);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('notifications.deleted'));
    }

    public function unreadCount(): JsonResponse
    {
        $this->authorize('notification.view');

        return response()->json([
            'count' => $this->service->getUnreadCount(Auth::id()),
        ]);
    }
}

<?php

namespace App\View\Composers;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function compose(View $view): void
    {
        $userId = Auth::id();

        $view->with(
            'unreadNotificationCount',
            $userId
            ? $this->notificationService->getUnreadCount($userId)
            : 0
        );
    }
}

@extends('layouts.app')

@section('title', __('notifications.notifications'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="bi bi-bell me-1"></i> {{ __('notifications.notifications') }}
        @if($unreadCount > 0)
            <span class="badge bg-danger">{{ $unreadCount }}</span>
        @endif
    </h4>
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-check2-all me-1"></i> {{ __('notifications.mark_all_read') }}
            </button>
        </form>
    @endif
</div>

<div class="card">
    <div class="card-body p-0">
        @forelse($notifications as $notification)
            <div class="d-flex align-items-start p-3 border-bottom {{ $notification->isRead() ? '' : 'bg-light' }}">
                {{-- Icon by type --}}
                <div class="me-3 mt-1">
                    @if($notification->type === 'security')
                        <i class="bi bi-shield-exclamation fs-5 text-danger"></i>
                    @elseif($notification->type === 'reminder')
                        <i class="bi bi-alarm fs-5 text-warning"></i>
                    @elseif($notification->type === 'administrative')
                        <i class="bi bi-gear fs-5 text-info"></i>
                    @else
                        <i class="bi bi-info-circle fs-5 text-primary"></i>
                    @endif
                </div>

                {{-- Content --}}
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <strong class="{{ $notification->isRead() ? 'fw-normal' : '' }}">{{ $notification->title }}</strong>
                        <small class="text-muted ms-2 text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="text-muted small mt-1">{{ $notification->message }}</div>
                    <div class="mt-1">
                        <span class="badge bg-secondary">{{ $notification->type }}</span>
                        @if($notification->priority === 'critical')
                            <span class="badge bg-danger">{{ $notification->priority }}</span>
                        @elseif($notification->priority === 'high')
                            <span class="badge bg-warning text-dark">{{ $notification->priority }}</span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="ms-2 d-flex gap-1">
                    @if(!$notification->isRead())
                        <form method="POST" action="{{ route('notifications.mark-read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success" title="{{ __('notifications.mark_read') }}">
                                <i class="bi bi-check"></i>
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('notifications.delete') }}"
                            onclick="return confirm('{{ __('notifications.confirm_delete') }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                {{ __('notifications.no_notifications') }}
            </div>
        @endforelse
    </div>
</div>

@if($notifications->hasPages())
    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
@endif
@endsection

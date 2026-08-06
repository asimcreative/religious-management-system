@extends('layouts.app')

@section('title', __('notifications.notifications'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('notifications.notifications') }}</li>
@endsection

@section('content')
@php
    // Type drives the icon and tone so a user can scan the list by shape and
    // colour before reading a single word.
    $typeMeta = [
        'security' => ['icon' => 'bi-shield-exclamation', 'soft' => 'var(--rams-danger-soft)',  'tone' => 'var(--rams-danger-text)'],
        'reminder' => ['icon' => 'bi-alarm',              'soft' => 'var(--rams-warning-soft)', 'tone' => 'var(--rams-warning-text)'],
        'administrative' => ['icon' => 'bi-gear',         'soft' => 'var(--rams-info-soft)',    'tone' => 'var(--rams-info-text)'],
        'system'   => ['icon' => 'bi-info-circle',        'soft' => 'var(--rams-primary-soft)', 'tone' => 'var(--rams-primary-text)'],
    ];
@endphp

<x-page-header :title="__('notifications.notifications')"
               :subtitle="__('notifications.subtitle')"
               icon="bi-bell"
               :badge="$unreadCount > 0 ? __('notifications.unread_count', ['count' => $unreadCount]) : null"
               badge-tone="danger">
    <x-slot:actions>
        @can('notification.read')
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-check2-all" aria-hidden="true"></i>
                        <span>{{ __('notifications.mark_all_read') }}</span>
                    </button>
                </form>
            @endif
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- `notifications-list` keeps the region identifiable in both the populated
     and the empty state, for tests and assistive technology alike. --}}
<x-card flush class="notifications-list">
    @forelse ($notifications as $notification)
        @php($meta = $typeMeta[$notification->type] ?? $typeMeta['system'])

        <article class="notification-item {{ $notification->isRead() ? '' : 'is-unread' }}">
            <span class="notification-item__icon"
                  style="--tone-soft: {{ $meta['soft'] }}; --tone: {{ $meta['tone'] }};"
                  aria-hidden="true">
                <i class="bi {{ $meta['icon'] }}"></i>
            </span>

            <div class="notification-item__body">
                <h2 class="notification-item__title h6 mb-0">
                    {{ $notification->title }}
                    @unless ($notification->isRead())
                        <span class="visually-hidden">({{ __('notifications.unread') }})</span>
                    @endunless
                </h2>

                <p class="notification-item__text mb-0">{{ $notification->message }}</p>

                <div class="notification-item__meta">
                    <span class="badge-soft badge-soft-neutral badge-soft--plain">
                        {{ __('notifications.types.'.$notification->type) }}
                    </span>

                    @if ($notification->priority === 'critical')
                        <span class="badge-soft badge-soft-danger">{{ __('notifications.priorities.critical') }}</span>
                    @elseif ($notification->priority === 'high')
                        <span class="badge-soft badge-soft-warning">{{ __('notifications.priorities.high') }}</span>
                    @endif

                    <time datetime="{{ $notification->created_at->toIso8601String() }}"
                          title="{{ $notification->created_at->format('d M Y, H:i') }}">
                        {{ $notification->created_at->diffForHumans() }}
                    </time>
                </div>
            </div>

            <div class="notification-item__actions">
                @can('notification.read')
                    @unless ($notification->isRead())
                        <form method="POST" action="{{ route('notifications.mark-read', $notification->id) }}" data-no-loading>
                            @csrf
                            <button type="submit" class="btn btn-sm btn-ghost btn-icon"
                                    data-bs-toggle="tooltip" title="{{ __('notifications.mark_read') }}"
                                    aria-label="{{ __('notifications.mark_read') }} — {{ $notification->title }}">
                                <i class="bi bi-check2" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endunless
                @endcan

                @can('notification.delete')
                    <x-delete-button :action="route('notifications.destroy', $notification->id)"
                                     :confirm="__('notifications.confirm_delete')"
                                     :title="__('notifications.delete')" />
                @endcan
            </div>
        </article>
    @empty
        <x-empty-state icon="bi-bell-slash"
                       :title="__('notifications.no_notifications')"
                       :text="__('notifications.empty_text')" />
    @endforelse

    @if ($notifications->hasPages())
        <div class="table-footer">
            <p class="table-footer__count mb-0">
                {{ __('ui.showing_results', [
                    'first' => number_format($notifications->firstItem() ?? 0),
                    'last'  => number_format($notifications->lastItem() ?? 0),
                    'total' => number_format($notifications->total()),
                ]) }}
            </p>
            {{ $notifications->links() }}
        </div>
    @endif
</x-card>
@endsection

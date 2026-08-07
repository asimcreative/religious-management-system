@extends('layouts.app')

@section('title', __('dashboard.dashboard'))

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? __('ui.good_morning') : ($hour < 17 ? __('ui.good_afternoon') : __('ui.good_evening'));
    $firstName = str(auth()->user()?->name ?? '')->before(' ')->toString();

    $greetingLine = $firstName === ''
        ? $greeting
        : __('ui.greeting_named', ['greeting' => $greeting, 'name' => $firstName]);

    $today = today();
@endphp

<x-page-header :title="$greetingLine"
               :subtitle="__('platform.subtitle')"
               icon="bi-buildings">
    <x-slot:actions>
        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul" aria-hidden="true"></i>
            <span>{{ __('platform.all_companies') }}</span>
        </a>
        @can('create', App\Models\Company::class)
            <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>{{ __('companies.add_new') }}</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- ── Key figures ──────────────────────────────────────────────────────── --}}
<div class="auto-grid auto-grid--sm mb-4">
    <x-stat-card
        :label="__('platform.companies')"
        :value="number_format($overview['total_companies'])"
        icon="bi-buildings"
        tone="primary"
        :href="route('companies.index')"
        :hint="__('ui.view_all')">
        <x-slot:meta>
            <span class="badge-soft badge-soft-success">{{ number_format($overview['active_companies']) }} {{ __('dashboard.active') }}</span>
            @if ($overview['inactive_companies'] > 0)
                <span class="badge-soft badge-soft-neutral">{{ number_format($overview['inactive_companies']) }} {{ __('dashboard.inactive') }}</span>
            @endif
        </x-slot:meta>
    </x-stat-card>

    <x-stat-card
        :label="__('platform.user_accounts')"
        :value="number_format($overview['total_users'])"
        icon="bi-person-badge"
        tone="info">
        <x-slot:meta>
            <span class="badge-soft badge-soft-success">{{ number_format($overview['active_users']) }} {{ __('dashboard.active') }}</span>
        </x-slot:meta>
    </x-stat-card>

    <x-stat-card
        :label="__('platform.employee_records')"
        :value="number_format($overview['total_employees'])"
        icon="bi-people"
        tone="accent">
        <x-slot:meta>
            <span class="fs-xs text-soft">{{ __('platform.across_all_companies') }}</span>
        </x-slot:meta>
    </x-stat-card>

    <x-stat-card
        :label="__('platform.subscriptions')"
        :value="number_format($overview['expiring_soon'] + $overview['expired'])"
        icon="bi-calendar-event"
        :tone="$overview['expired'] > 0 ? 'danger' : ($overview['expiring_soon'] > 0 ? 'warning' : 'success')">
        <x-slot:meta>
            @if ($overview['expired'] > 0)
                <span class="badge-soft badge-soft-danger">{{ number_format($overview['expired']) }} {{ __('platform.expired') }}</span>
            @endif
            @if ($overview['expiring_soon'] > 0)
                <span class="badge-soft badge-soft-warning">{{ number_format($overview['expiring_soon']) }} {{ __('platform.expiring_soon') }}</span>
            @endif
            @if ($overview['expired'] === 0 && $overview['expiring_soon'] === 0)
                <span class="badge-soft badge-soft-success">{{ __('platform.all_current') }}</span>
            @endif
        </x-slot:meta>
    </x-stat-card>
</div>

{{-- ── Subscriptions needing attention ──────────────────────────────────── --}}
@if ($attention->isNotEmpty())
    <h2 class="section-heading">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>{{ __('platform.needs_attention') }}
    </h2>

    <x-card flush class="mb-4">
        <x-table :label="__('platform.needs_attention')">
            <thead>
                <tr>
                    <th scope="col">{{ __('companies.company_name') }}</th>
                    <th scope="col">{{ __('companies.subscription_plan') }}</th>
                    <th scope="col">{{ __('companies.subscription_expiry') }}</th>
                    <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('companies.actions') }}</span></th>
                </tr>
            </thead>

            <tbody>
                @foreach ($attention as $company)
                    @php($expired = $company->subscription_expiry?->lt($today))

                    <tr>
                        <td data-label="{{ __('companies.company_name') }}">
                            <span class="cell-primary__text">
                                <span class="cell-primary__title">{{ $company->company_name }}</span>
                                <span class="cell-primary__sub code-cell">{{ $company->company_code }}</span>
                            </span>
                        </td>

                        <td data-label="{{ __('companies.subscription_plan') }}">{{ $company->subscription_plan ?: '—' }}</td>

                        <td data-label="{{ __('companies.subscription_expiry') }}">
                            <span class="badge-soft badge-soft-{{ $expired ? 'danger' : 'warning' }}">
                                {{ $company->subscription_expiry?->translatedFormat('d M Y') }}
                            </span>
                            <span class="d-block fs-xs text-soft">
                                {{ $expired ? __('platform.expired') : __('platform.expiring_soon') }}
                            </span>
                        </td>

                        <td class="col-actions" data-label="{{ __('companies.actions') }}">
                            <div class="table-actions">
                                <x-impersonate-button :company="$company" />

                                @can('update', $company)
                                    <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-ghost btn-icon"
                                       data-bs-toggle="tooltip" title="{{ __('companies.edit') }}"
                                       aria-label="{{ __('companies.edit') }} — {{ $company->company_name }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
@endif

{{-- ── Newest tenants ───────────────────────────────────────────────────── --}}
<h2 class="section-heading">
    <i class="bi bi-buildings" aria-hidden="true"></i>{{ __('platform.recent_companies') }}
</h2>

<x-card flush>
    <x-slot:actions>
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-ghost">
            {{ __('ui.view_all') }}<i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </x-slot:actions>

    <x-table :label="__('platform.recent_companies')">
        <thead>
            <tr>
                <th scope="col">{{ __('companies.company_name') }}</th>
                <th scope="col">{{ __('companies.user_count') }}</th>
                <th scope="col">{{ __('platform.employee_records') }}</th>
                <th scope="col">{{ __('companies.status') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('companies.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($companies as $company)
                <tr>
                    <td data-label="{{ __('companies.company_name') }}">
                        <span class="cell-primary__text">
                            <span class="cell-primary__title">{{ $company->company_name }}</span>
                            <span class="cell-primary__sub code-cell">{{ $company->company_code }}</span>
                        </span>
                    </td>

                    <td data-label="{{ __('companies.user_count') }}" class="mono">{{ number_format($company->users_count) }}</td>
                    <td data-label="{{ __('platform.employee_records') }}" class="mono">{{ number_format($company->employees_count) }}</td>

                    <td data-label="{{ __('companies.status') }}">
                        <x-status-badge :status="$company->status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('companies.actions') }}">
                        <div class="table-actions">
                            <x-impersonate-button :company="$company" />

                            @can('update', $company)
                                <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('companies.edit') }}"
                                   aria-label="{{ __('companies.edit') }} — {{ $company->company_name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="bi-buildings"
                                       :title="__('companies.empty_title')"
                                       :text="__('companies.empty_text')">
                            @can('create', App\Models\Company::class)
                                <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    <span>{{ __('companies.add_new') }}</span>
                                </a>
                            @endcan
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</x-card>
@endsection

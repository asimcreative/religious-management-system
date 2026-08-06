@extends('layouts.app')

@section('title', __('users.users'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('users.users') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];

    if (filled(request('search'))) {
        $activeFilters += $chip('search', __('ui.search').': '.request('search'));
    }
    if (filled(request('role'))) {
        $activeFilters += $chip('role', __('users.roles').': '.request('role'));
    }
    if (filled(request('status'))) {
        $case = App\Enums\Status::tryFrom((int) request('status'));
        if ($case) {
            $activeFilters += $chip('status', __('users.status').': '.$case->label());
        }
    }
@endphp

<x-page-header :title="__('users.users')"
               :subtitle="__('users.subtitle')"
               icon="bi-person-badge"
               :badge="number_format($users->total())">
    <x-slot:actions>
        <x-data-toolbar resource="users"
                        :create-route="route('users.create')"
                        :create-model="App\Models\User::class"
                        :create-label="__('users.add_new')"
                        :filters="request()->query()" />
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('users.index')">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('users.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>

        <div class="field field--md">
            <label for="filter_role" class="form-label">{{ __('users.roles') }}</label>
            <select name="role" id="filter_role" class="form-select form-select-sm">
                <option value="">{{ __('users.all_roles') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--sm">
            <label for="filter_status" class="form-label">{{ __('users.status') }}</label>
            <select name="status" id="filter_status" class="form-select form-select-sm">
                <option value="">{{ __('users.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <x-table sticky :label="__('users.users')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('users.name') }}</th>
                <th scope="col">{{ __('users.mobile') }}</th>
                <th scope="col">{{ __('users.roles') }}</th>
                <th scope="col">{{ __('users.last_login') }}</th>
                <th scope="col">{{ __('users.status') }}</th>
                <th scope="col" class="col-actions">
                    <span class="visually-hidden">{{ __('users.actions') }}</span>
                </th>
            </tr>
        </thead>

        <tbody>
            @forelse ($users as $record)
                <tr>
                    <td class="col-num" data-label="#">{{ $users->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('users.name') }}">
                        <div class="cell-primary">
                            <x-avatar :name="$record->name" />
                            <span class="cell-primary__text">
                                @can('view', $record)
                                    <a href="{{ route('users.show', $record) }}" class="cell-primary__title">{{ $record->name }}</a>
                                @else
                                    <span class="cell-primary__title">{{ $record->name }}</span>
                                @endcan
                                <span class="cell-primary__sub">{{ $record->email }}</span>
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('users.mobile') }}">
                        @if ($record->mobile)
                            <a href="tel:{{ $record->mobile }}" class="mono">{{ $record->mobile }}</a>
                        @else
                            <span class="dash">—</span>
                        @endif
                    </td>

                    <td data-label="{{ __('users.roles') }}">
                        @forelse ($record->roles as $role)
                            <span class="badge-soft badge-soft-primary">{{ $role->name }}</span>
                        @empty
                            <span class="dash">{{ __('users.no_roles') }}</span>
                        @endforelse
                    </td>

                    <td data-label="{{ __('users.last_login') }}" class="mono fs-xs">
                        {{ $record->last_login
                            ? App\Helpers\TimezoneHelper::formatForDisplay($record->last_login, 'Y-m-d H:i')
                            : __('users.never_signed_in') }}
                    </td>

                    <td data-label="{{ __('users.status') }}">
                        <x-status-badge :status="$record->status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('users.actions') }}">
                        <div class="table-actions">
                            @can('view', $record)
                                <a href="{{ route('users.show', $record) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('users.view') }}"
                                   aria-label="{{ __('users.view') }} — {{ $record->name }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                            @endcan

                            @can('update', $record)
                                <a href="{{ route('users.edit', $record) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('users.edit') }}"
                                   aria-label="{{ __('users.edit') }} — {{ $record->name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan

                            @can('delete', $record)
                                <x-delete-button :action="route('users.destroy', $record)"
                                                 :record="$record->name"
                                                 :title="__('users.delete')" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search"
                                           :title="__('ui.no_results_title')"
                                           :text="__('ui.no_results_text')">
                                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-person-badge"
                                           :title="__('users.empty_title')"
                                           :text="__('users.empty_text')">
                                @can('create', App\Models\User::class)
                                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        <span>{{ __('users.add_new') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$users" />
</x-card>
@endsection

@extends('layouts.app')

@section('title', __('roles.roles'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('roles.roles') }}</li>
@endsection

@section('content')
@php($hasSearch = filled(request('search')))

<x-page-header :title="__('roles.roles')"
               :subtitle="__('roles.subtitle')"
               icon="bi-shield-lock"
               :badge="number_format($roles->total())">
    <x-slot:actions>
        @can('role.create')
            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>{{ __('roles.add_new') }}</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :active="$hasSearch ? [__('ui.search').': '.request('search') => route('roles.index')] : []"
               :reset-url="$hasSearch ? route('roles.index') : null">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('roles.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>
    </x-filters>

    <x-table sticky :label="__('roles.roles')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('roles.name') }}</th>
                <th scope="col">{{ __('roles.permission_count') }}</th>
                <th scope="col">{{ __('roles.user_count') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('roles.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($roles as $role)
                <tr>
                    <td class="col-num" data-label="#">{{ $roles->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('roles.name') }}">
                        <span class="fw-semibold text-strong">{{ $role->name }}</span>
                        @if ($service->isProtected($role))
                            <span class="badge-soft badge-soft-neutral">{{ __('roles.protected') }}</span>
                        @endif
                    </td>

                    <td data-label="{{ __('roles.permission_count') }}" class="mono">
                        {{ number_format($role->permissions_count) }}
                    </td>

                    <td data-label="{{ __('roles.user_count') }}" class="mono">
                        {{ number_format($role->users_count) }}
                    </td>

                    <td class="col-actions" data-label="{{ __('roles.actions') }}">
                        <div class="table-actions">
                            @can('role.update')
                                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('roles.edit') }}"
                                   aria-label="{{ __('roles.edit') }} — {{ $role->name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan

                            @can('role.delete')
                                @if ($service->canDelete($role))
                                    <x-delete-button :action="route('roles.destroy', $role->id)"
                                                     :record="$role->name"
                                                     :title="__('roles.delete')" />
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-empty-state icon="bi-shield-lock"
                                       :title="__('roles.empty_title')"
                                       :text="__('roles.empty_text')">
                            @can('role.create')
                                <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    <span>{{ __('roles.add_new') }}</span>
                                </a>
                            @endcan
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$roles" />
</x-card>
@endsection

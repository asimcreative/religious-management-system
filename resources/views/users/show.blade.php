@extends('layouts.app')

@section('title', $user->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ __('users.users') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
@endsection

@section('content')
<x-page-header :title="$user->name" :subtitle="$user->email" icon="bi-person-badge">
    <x-slot:actions>
        @can('update', $user)
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>{{ __('users.edit') }}</span>
            </a>
        @endcan

        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('users.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-7">
        <x-card :title="__('users.account_details')" icon="bi-person-badge">
            <x-detail-list>
                <x-detail-row :label="__('users.name')" :value="$user->name" />
                <x-detail-row :label="__('users.email')" :value="$user->email" />
                <x-detail-row :label="__('users.mobile')" :value="$user->mobile" />
                <x-detail-row :label="__('users.language')" :value="$user->language" />
                <x-detail-row :label="__('users.company')" :value="$user->company?->company_name" />
            </x-detail-list>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card :title="__('users.access')" icon="bi-shield-check">
            <x-detail-list>
                <x-detail-row :label="__('users.status')">
                    <x-status-badge :status="$user->status" />
                </x-detail-row>

                <x-detail-row :label="__('users.roles')">
                    @forelse ($user->roles as $role)
                        <span class="badge-soft badge-soft-primary">{{ $role->name }}</span>
                    @empty
                        <span class="dash">{{ __('users.no_roles') }}</span>
                    @endforelse
                </x-detail-row>

                <x-detail-row :label="__('users.last_login')"
                              :value="$user->last_login
                                  ? App\Helpers\TimezoneHelper::formatForDisplay($user->last_login, 'Y-m-d H:i')
                                  : __('users.never_signed_in')" />
            </x-detail-list>
        </x-card>
    </div>
</div>
@endsection

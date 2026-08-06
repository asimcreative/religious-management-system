@extends('layouts.app')

@section('title', __('roles.edit_role'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">{{ __('roles.roles') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $role->name }}</li>
@endsection

@section('content')
<x-page-header :title="__('roles.edit_role')" :subtitle="$role->name" icon="bi-shield-lock">
    <x-slot:actions>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('roles.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST" action="{{ route('roles.update', $role->id) }}" novalidate data-guard>
    @csrf
    @method('PUT')

    <x-card>
        @include('roles.partials.form')
    </x-card>

    <x-form.actions :submit="__('roles.save')" :cancel-url="route('roles.index')" />
</form>
@endsection

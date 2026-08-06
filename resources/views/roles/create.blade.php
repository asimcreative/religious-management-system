@extends('layouts.app')

@section('title', __('roles.create_role'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">{{ __('roles.roles') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('roles.create_role') }}</li>
@endsection

@section('content')
<x-page-header :title="__('roles.create_role')" :subtitle="__('roles.create_subtitle')" icon="bi-shield-plus">
    <x-slot:actions>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('roles.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST" action="{{ route('roles.store') }}" novalidate data-guard>
    @csrf

    <x-card>
        @include('roles.partials.form', ['role' => null, 'isProtected' => false])
    </x-card>

    <x-form.actions :submit="__('roles.save')" :cancel-url="route('roles.index')" />
</form>
@endsection

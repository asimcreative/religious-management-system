@extends('layouts.app')

@section('title', __('users.create_user'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ __('users.users') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('users.create_user') }}</li>
@endsection

@section('content')
<x-page-header :title="__('users.create_user')" :subtitle="__('users.create_subtitle')" icon="bi-person-plus">
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('users.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST" action="{{ route('users.store') }}" novalidate data-guard>
    @csrf

    <x-card>
        @include('users.partials.form', ['user' => null])
    </x-card>

    <x-form.actions :submit="__('users.save')" :cancel-url="route('users.index')" />
</form>
@endsection

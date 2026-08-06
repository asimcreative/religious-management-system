@extends('layouts.app')

@section('title', __('users.edit_user'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ __('users.users') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
@endsection

@section('content')
<x-page-header :title="__('users.edit_user')" :subtitle="$user->email" icon="bi-person-gear">
    <x-slot:actions>
        <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('users.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST" action="{{ route('users.update', $user) }}" novalidate data-guard>
    @csrf
    @method('PUT')

    <x-card>
        @include('users.partials.form')
    </x-card>

    <x-form.actions :submit="__('users.save')" :cancel-url="route('users.show', $user)" />
</form>
@endsection

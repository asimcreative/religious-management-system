@extends('layouts.app')

@section('title', __('employees.create_employee'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">{{ __('employees.employees') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('employees.create_employee') }}</li>
@endsection

@section('content')
<x-page-header :title="__('employees.create_employee')"
               :subtitle="__('employees.create_subtitle')"
               icon="bi-person-plus">
    <x-slot:actions>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('employees.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

{{-- novalidate: validation is answered by the server, so every message is
     localised (English / Urdu) and styled consistently instead of relying on
     the browser's own untranslatable bubbles. --}}
<form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data" novalidate data-guard>
    @csrf

    @include('employees.partials.form', ['employee' => null])

    <x-form.actions :submit="__('employees.save')" :cancel-url="route('employees.index')" />
</form>
@endsection

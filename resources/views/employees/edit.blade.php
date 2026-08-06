@extends('layouts.app')

@section('title', __('employees.edit_employee'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">{{ __('employees.employees') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->employee_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('employees.edit') }}</li>
@endsection

@section('content')
<x-page-header :title="__('employees.edit_employee')"
               :subtitle="$employee->employee_name.' · '.$employee->employee_code"
               icon="bi-pencil-square">
    <x-slot:actions>
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('employees.back_to_detail') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data" novalidate data-guard>
    @csrf
    @method('PUT')

    @include('employees.partials.form', ['employee' => $employee])

    <x-form.actions :submit="__('employees.save')" :cancel-url="route('employees.show', $employee)" />
</form>
@endsection

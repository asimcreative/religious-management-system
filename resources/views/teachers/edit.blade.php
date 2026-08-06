@extends('layouts.app')

@section('title', __('teachers.edit_teacher'))

@php
    // The controller eager-loads only `branches`, but this screen also needs
    // the linked employee to pre-select it (that employee is excluded from
    // `$employees`). loadMissing keeps the page safe under
    // Model::preventLazyLoading without changing any controller.
    $teacher->loadMissing('employee');
@endphp

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">{{ __('teachers.teachers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('teachers.show', $teacher) }}">{{ $teacher->employee?->employee_name ?? $teacher->teacher_code }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('teachers.edit') }}</li>
@endsection

@section('content')
<x-page-header :title="__('teachers.edit_teacher')"
               :subtitle="($teacher->employee?->employee_name ?? '—').' · '.$teacher->teacher_code"
               icon="bi-pencil-square">
    <x-slot:actions>
        <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('teachers.back_to_detail') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('teachers.update', $teacher) }}" novalidate data-guard>
    @csrf
    @method('PUT')

    @include('teachers.partials.form', ['teacher' => $teacher])

    <x-form.actions :submit="__('teachers.save')" :cancel-url="route('teachers.show', $teacher)" />
</form>
@endsection

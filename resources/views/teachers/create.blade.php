@extends('layouts.app')

@section('title', __('teachers.create_teacher'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">{{ __('teachers.teachers') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('teachers.create_teacher') }}</li>
@endsection

@section('content')
<x-page-header :title="__('teachers.create_teacher')"
               :subtitle="__('teachers.create_subtitle')"
               icon="bi-person-plus">
    <x-slot:actions>
        <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('teachers.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('teachers.store') }}" novalidate data-guard>
    @csrf

    @include('teachers.partials.form', ['teacher' => null])

    <x-form.actions :submit="__('teachers.save')" :cancel-url="route('teachers.index')" />
</form>
@endsection

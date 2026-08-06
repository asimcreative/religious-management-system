@extends('layouts.app')

@section('title', __('masters.edit').' — '.$department->department_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.departments.index') }}">{{ __('masters.departments') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $department->department_name }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.edit').' — '.$department->department_name,
    'singular' => __('masters.department'),
    'plural' => __('masters.departments'),
    'icon' => 'bi-diagram-3',
    'routeBase' => 'masters.departments',
    'fieldsView' => 'masters.departments.partials.fields',
    'record' => $department,
    'aside' => [__('masters.department_use_1'), __('masters.department_use_2')],
])
@endsection

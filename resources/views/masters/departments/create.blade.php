@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.department'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.departments.index') }}">{{ __('masters.departments') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.department'),
    'singular' => __('masters.department'),
    'plural' => __('masters.departments'),
    'intro' => __('masters.departments_intro'),
    'icon' => 'bi-diagram-3',
    'routeBase' => 'masters.departments',
    'fieldsView' => 'masters.departments.partials.fields',
    'record' => null,
    'aside' => [__('masters.department_use_1'), __('masters.department_use_2')],
])
@endsection

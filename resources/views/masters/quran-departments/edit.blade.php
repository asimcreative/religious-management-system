@extends('layouts.app')

@section('title', __('masters.edit').' — '.$department->department_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.quran-departments.index') }}">{{ __('masters.quran_departments') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $department->department_name }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.edit').' — '.$department->department_name,
    'singular' => __('masters.quran_department'),
    'plural' => __('masters.quran_departments'),
    'icon' => 'bi-bookmarks',
    'routeBase' => 'masters.quran-departments',
    'fieldsView' => 'masters.quran-departments.partials.fields',
    'record' => $department,
    'aside' => [__('masters.quran_department_use_1'), __('masters.quran_department_use_2')],
])
@endsection

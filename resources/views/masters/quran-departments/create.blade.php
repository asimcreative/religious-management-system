@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.quran_department'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.quran-departments.index') }}">{{ __('masters.quran_departments') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.quran_department'),
    'singular' => __('masters.quran_department'),
    'plural' => __('masters.quran_departments'),
    'intro' => __('masters.quran_departments_intro'),
    'icon' => 'bi-bookmarks',
    'routeBase' => 'masters.quran-departments',
    'fieldsView' => 'masters.quran-departments.partials.fields',
    'record' => null,
    'aside' => [__('masters.quran_department_use_1'), __('masters.quran_department_use_2')],
])
@endsection

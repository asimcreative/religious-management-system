@extends('layouts.app')

@section('title', __('masters.edit').' — '.$designation->designation_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.designations.index') }}">{{ __('masters.designations') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $designation->designation_name }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.edit').' — '.$designation->designation_name,
    'singular' => __('masters.designation'),
    'plural' => __('masters.designations'),
    'icon' => 'bi-award',
    'routeBase' => 'masters.designations',
    'fieldsView' => 'masters.designations.partials.fields',
    'record' => $designation,
    'aside' => [__('masters.designation_use_1'), __('masters.designation_use_2')],
])
@endsection

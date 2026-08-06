@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.designation'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.designations.index') }}">{{ __('masters.designations') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.designation'),
    'singular' => __('masters.designation'),
    'plural' => __('masters.designations'),
    'intro' => __('masters.designations_intro'),
    'icon' => 'bi-award',
    'routeBase' => 'masters.designations',
    'fieldsView' => 'masters.designations.partials.fields',
    'record' => null,
    'aside' => [__('masters.designation_use_1'), __('masters.designation_use_2')],
])
@endsection

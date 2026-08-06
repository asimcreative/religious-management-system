@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.branch'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.branches.index') }}">{{ __('masters.branches') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.branch'),
    'singular' => __('masters.branch'),
    'plural' => __('masters.branches'),
    'intro' => __('masters.branches_intro'),
    'icon' => 'bi-building',
    'routeBase' => 'masters.branches',
    'fieldsView' => 'masters.branches.partials.fields',
    'record' => null,
    'aside' => [__('masters.branch_use_1'), __('masters.branch_use_2'), __('masters.branch_use_3')],
])
@endsection

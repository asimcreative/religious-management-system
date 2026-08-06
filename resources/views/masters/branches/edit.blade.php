@extends('layouts.app')

@section('title', __('masters.edit').' — '.$branch->branch_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.branches.index') }}">{{ __('masters.branches') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $branch->branch_name }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.edit').' — '.$branch->branch_name,
    'singular' => __('masters.branch'),
    'plural' => __('masters.branches'),
    'icon' => 'bi-building',
    'routeBase' => 'masters.branches',
    'fieldsView' => 'masters.branches.partials.fields',
    'record' => $branch,
    'aside' => [__('masters.branch_use_1'), __('masters.branch_use_2'), __('masters.branch_use_3')],
])
@endsection

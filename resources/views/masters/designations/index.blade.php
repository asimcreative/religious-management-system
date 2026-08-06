@extends('layouts.app')

@section('title', __('masters.designations'))

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ __('masters.master_data') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.designations') }}</li>
@endsection

@section('content')
@include('masters.partials.index', [
    'title' => __('masters.designations'),
    'singular' => __('masters.designation'),
    'intro' => __('masters.designations_intro'),
    'icon' => 'bi-award',
    'routeBase' => 'masters.designations',
    'transferResource' => 'designations',
    'model' => App\Models\Designation::class,
    'records' => $designations,
    'nameFor' => fn ($record) => $record->designation_name,
    'columns' => [
        ['label' => __('masters.designation_name'), 'primary' => true, 'value' => fn ($r) => $r->designation_name],
    ],
])
@endsection

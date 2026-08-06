@extends('layouts.app')

@section('title', __('masters.departments'))

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ __('masters.master_data') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.departments') }}</li>
@endsection

@section('content')
@include('masters.partials.index', [
    'title' => __('masters.departments'),
    'singular' => __('masters.department'),
    'intro' => __('masters.departments_intro'),
    'icon' => 'bi-diagram-3',
    'routeBase' => 'masters.departments',
    'transferResource' => 'departments',
    'model' => App\Models\Department::class,
    'records' => $departments,
    'nameFor' => fn ($record) => $record->department_name,
    'columns' => [
        ['label' => __('masters.department_name'), 'primary' => true, 'value' => fn ($r) => $r->department_name],
    ],
])
@endsection

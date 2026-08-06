@extends('layouts.app')

@section('title', __('masters.branches'))

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ __('masters.master_data') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.branches') }}</li>
@endsection

@section('content')
@include('masters.partials.index', [
    'title' => __('masters.branches'),
    'singular' => __('masters.branch'),
    'intro' => __('masters.branches_intro'),
    'icon' => 'bi-building',
    'routeBase' => 'masters.branches',
    'model' => App\Models\Branch::class,
    'records' => $branches,
    'nameFor' => fn ($record) => $record->branch_name,
    'columns' => [
        ['label' => __('masters.branch_name'), 'primary' => true, 'value' => fn ($r) => $r->branch_name],
        ['label' => __('masters.address'), 'value' => fn ($r) => $r->address],
        ['label' => __('masters.phone'), 'value' => fn ($r) => $r->phone, 'class' => 'mono'],
    ],
])
@endsection

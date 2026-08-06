@extends('layouts.app')

@section('title', __('masters.quran_departments'))

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ __('masters.master_data') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.quran_departments') }}</li>
@endsection

@section('content')
@include('masters.partials.index', [
    'title' => __('masters.quran_departments'),
    'singular' => __('masters.quran_department'),
    'intro' => __('masters.quran_departments_intro'),
    'icon' => 'bi-bookmarks',
    'routeBase' => 'masters.quran-departments',
    'transferResource' => 'quran-departments',
    'model' => App\Models\QuranDepartment::class,
    'records' => $departments,
    'nameFor' => fn ($record) => $record->department_name,
    'columns' => [
        ['label' => __('masters.department_name'), 'primary' => true, 'value' => fn ($r) => $r->department_name],
        ['label' => __('masters.description'), 'value' => fn ($r) => Str::limit($r->description, 60)],
        ['label' => __('masters.display_order'), 'value' => fn ($r) => $r->display_order, 'class' => 'col-fit tabular'],
    ],
])
@endsection

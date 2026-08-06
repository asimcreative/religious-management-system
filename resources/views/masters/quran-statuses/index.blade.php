@extends('layouts.app')

@section('title', __('masters.quran_statuses'))

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ __('masters.master_data') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.quran_statuses') }}</li>
@endsection

@section('content')
@include('masters.partials.index', [
    'title' => __('masters.quran_statuses'),
    'singular' => __('masters.quran_status'),
    'intro' => __('masters.quran_statuses_intro'),
    'icon' => 'bi-patch-check',
    'routeBase' => 'masters.quran-statuses',
    'transferResource' => 'quran-statuses',
    'model' => App\Models\QuranStatus::class,
    'records' => $statuses,
    'nameFor' => fn ($record) => $record->status_name,
    'columns' => [
        [
            'label' => __('masters.status_name'),
            'html' => true,
            'value' => fn ($r) => new Illuminate\Support\HtmlString(
                '<span class="badge-soft" style="background-color:'.e($r->color ?? '#64748B').'1F;color:'.e($r->color ?? '#64748B').'">'
                .e($r->status_name).'</span>'
            ),
        ],
        ['label' => __('masters.display_order'), 'value' => fn ($r) => $r->display_order, 'class' => 'col-fit tabular'],
    ],
])
@endsection

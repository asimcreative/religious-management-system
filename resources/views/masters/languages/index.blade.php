@extends('layouts.app')

@section('title', __('masters.languages'))

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ __('masters.master_data') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.languages') }}</li>
@endsection

@section('content')
@include('masters.partials.index', [
    'title' => __('masters.languages'),
    'singular' => __('masters.language'),
    'intro' => __('masters.languages_intro'),
    'icon' => 'bi-translate',
    'routeBase' => 'masters.languages',
    'transferResource' => 'languages',
    'model' => App\Models\Language::class,
    'records' => $languages,
    'nameFor' => fn ($record) => $record->language_name,
    'columns' => [
        ['label' => __('masters.language_name'), 'primary' => true, 'value' => fn ($r) => $r->language_name],
        ['label' => __('masters.native_name'), 'value' => fn ($r) => $r->native_name],
        ['label' => __('masters.locale'), 'value' => fn ($r) => $r->locale, 'class' => 'col-fit mono'],
        [
            'label' => __('masters.direction'),
            'html' => true,
            'class' => 'col-fit',
            'value' => fn ($r) => new Illuminate\Support\HtmlString(
                '<span class="badge-soft badge-soft-neutral badge-soft--plain">'
                .e($r->direction === 'rtl' ? __('masters.rtl') : __('masters.ltr')).'</span>'
            ),
        ],
    ],
])
@endsection

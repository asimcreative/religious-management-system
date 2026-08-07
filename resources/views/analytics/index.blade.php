@extends('layouts.app')

@section('title', __('analytics.title'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.report_center') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('analytics.title') }}</li>
@endsection

@section('content')
<x-page-header :title="__('analytics.title')"
               :subtitle="__('analytics.subtitle')"
               icon="bi-diagram-3" />

<div class="auto-grid auto-grid--lg">
    @foreach ($datasets as $dataset)
        @php($tone = ['salah-attendance' => 'primary', 'quran-attendance' => 'success', 'quran-progress' => 'warning'][$dataset->key()] ?? 'info')
        @php($icon = ['salah-attendance' => 'bi-moon-stars', 'quran-attendance' => 'bi-journal-check', 'quran-progress' => 'bi-graph-up-arrow'][$dataset->key()] ?? 'bi-bar-chart')

        <a href="{{ route('reports.analysis.show', $dataset->key()) }}" class="card card-link-tile tone-{{ $tone }}">
            <div class="card-body">
                <span class="stat-card__icon mb-3" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>

                <h2 class="h6 mb-1">{{ $dataset->label() }}</h2>
                <p class="fs-sm text-soft mb-3">{{ $dataset->description() }}</p>

                {{-- The counts are the selling point: this is not one report,
                     it is every combination of these two numbers. --}}
                <p class="fs-xs text-soft mb-3">
                    <span class="badge-soft badge-soft-neutral">
                        {{ __('analytics.n_breakdowns', ['count' => count($dataset->dimensions())]) }}
                    </span>
                    <span class="badge-soft badge-soft-neutral">
                        {{ __('analytics.n_filters', ['count' => count($dataset->filters())]) }}
                    </span>
                </p>

                <span class="fs-sm fw-semibold text-brand d-inline-flex align-items-center gap-1">
                    {{ __('analytics.open') }}
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </span>
            </div>
        </a>
    @endforeach
</div>

<div class="alert alert-info mt-4" role="note">
    <i class="bi bi-lightbulb-fill alert__icon" aria-hidden="true"></i>
    <div class="alert__body">
        <strong class="alert__title">{{ __('analytics.tip_title') }}</strong>
        {{ __('analytics.tip_text') }}
    </div>
</div>
@endsection

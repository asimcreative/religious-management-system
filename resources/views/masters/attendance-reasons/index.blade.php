@extends('layouts.app')

@section('title', __('masters.attendance_reasons'))

@section('breadcrumbs')
    <li class="breadcrumb-item">{{ __('masters.master_data') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.attendance_reasons') }}</li>
@endsection

@section('content')
@include('masters.partials.index', [
    'title' => __('masters.attendance_reasons'),
    'singular' => __('masters.attendance_reason'),
    'intro' => __('masters.attendance_reasons_intro'),
    'icon' => 'bi-chat-left-text',
    'routeBase' => 'masters.attendance-reasons',
    'transferResource' => 'attendance-reasons',
    'model' => App\Models\AttendanceReason::class,
    'records' => $reasons,
    'nameFor' => fn ($record) => $record->reason_name,
    'columns' => [
        [
            'label' => __('masters.reason_name'),
            'html' => true,
            'value' => fn ($r) => new Illuminate\Support\HtmlString(
                '<span class="badge-soft" style="background-color:'.e($r->color ?? '#64748B').'1F;color:'.e($r->color ?? '#64748B').'">'
                .e($r->reason_name).'</span>'
            ),
        ],
        [
            'label' => __('masters.counts_as_absent'),
            'html' => true,
            'value' => fn ($r) => new Illuminate\Support\HtmlString(
                $r->counts_as_absent
                    ? '<span class="badge-soft badge-soft-danger">'.e(__('ui.yes')).'</span>'
                    : '<span class="badge-soft badge-soft-neutral">'.e(__('ui.no')).'</span>'
            ),
        ],
        [
            'label' => __('masters.counts_as_leave'),
            'html' => true,
            'value' => fn ($r) => new Illuminate\Support\HtmlString(
                $r->counts_as_leave
                    ? '<span class="badge-soft badge-soft-warning">'.e(__('ui.yes')).'</span>'
                    : '<span class="badge-soft badge-soft-neutral">'.e(__('ui.no')).'</span>'
            ),
        ],
    ],
])
@endsection

@extends('layouts.app')

@section('title', __('settings.settings'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('settings.settings') }}</li>
@endsection

@section('content')
<x-page-header :title="__('settings.settings')" :subtitle="__('settings.subtitle')" icon="bi-sliders" />

<x-form.error-summary />

<form method="POST" action="{{ route('settings.update') }}" novalidate data-guard>
    @csrf
    @method('PUT')

    <x-card>
        <x-form.section :title="__('settings.attendance')" icon="bi-clock-history" :hint="__('settings.attendance_hint')">
            <div class="row">
                <div class="col-md-6">
                    <x-form.input name="attendance_lock_time"
                                  type="time"
                                  :label="__('settings.attendance_lock_time')"
                                  :value="$values['attendance_lock_time']"
                                  required
                                  :help="__('settings.attendance_lock_time_help')" />
                </div>

                <div class="col-md-6">
                    <x-form.input name="max_backdated_attendance_days"
                                  type="number"
                                  min="0"
                                  max="365"
                                  :label="__('settings.max_backdated_attendance_days')"
                                  :value="$values['max_backdated_attendance_days']"
                                  required
                                  :help="__('settings.max_backdated_attendance_days_help')" />
                </div>
            </div>
        </x-form.section>
    </x-card>

    <x-form.actions :submit="__('settings.save')" />
</form>
@endsection

@extends('layouts.app')

@section('title', __('auth.change_password'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('auth.change_password') }}</li>
@endsection

@section('content')
<x-page-header :title="__('auth.change_password')"
               :subtitle="__('ui.change_password_sub')"
               icon="bi-shield-lock" />

<div class="row g-4">
    <div class="col-12 col-lg-7 col-xl-6">
        <x-form.error-summary />

        <x-card>
            <form method="POST" action="{{ route('password.change') }}">
                @csrf

                <x-form.password
                    name="current_password"
                    :label="__('auth.current_password')"
                    autocomplete="current-password"
                    required
                    autofocus
                />

                <hr class="my-4 border-subtle">

                <x-form.password
                    name="password"
                    :label="__('auth.new_password')"
                    :help="__('auth.password_requirements')"
                    autocomplete="new-password"
                    meter
                    required
                />

                <x-form.password
                    name="password_confirmation"
                    :label="__('auth.confirm_password')"
                    autocomplete="new-password"
                    required
                />

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        <span>{{ __('auth.change_password') }}</span>
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
                </div>
            </form>
        </x-card>
    </div>

    <div class="col-12 col-lg-5 col-xl-6">
        <x-card :title="__('ui.password_tips_title')" icon="bi-lightbulb">
            <ul class="stack-sm list-unstyled mb-0 fs-md text-soft">
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('ui.password_tip_length') }}</li>
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('ui.password_tip_mix') }}</li>
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('ui.password_tip_unique') }}</li>
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('ui.password_tip_reuse') }}</li>
                <li><i class="bi bi-x-lg text-danger me-2" aria-hidden="true"></i>{{ __('ui.password_tip_share') }}</li>
            </ul>
        </x-card>
    </div>
</div>
@endsection

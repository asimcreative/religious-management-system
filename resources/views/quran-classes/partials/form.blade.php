{{-- Shared Quran class form fields (create + edit). --}}
@php($quranClass = $quranClass ?? null)

<x-form.error-summary />

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <x-card :title="__('quran_classes.class_info')" icon="bi-book" class="mb-3">
            <div class="row">
                <div class="col-12 col-md-6">
                    <x-form.input name="class_code"
                                  :label="__('quran_classes.class_code')"
                                  :value="$quranClass?->class_code"
                                  :help="__('quran_classes.class_code_help')"
                                  required
                                  autocomplete="off" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.input name="class_name"
                                  :label="__('quran_classes.class_name')"
                                  :value="$quranClass?->class_name"
                                  required
                                  autocomplete="off" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="teacher_id"
                                   :label="__('quran_classes.teacher')"
                                   :selected="$quranClass?->teacher_id"
                                   :placeholder="__('quran_classes.select')"
                                   :options="$teachers"
                                   required />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="branch_id"
                                   :label="__('quran_classes.branch')"
                                   :selected="$quranClass?->branch_id"
                                   :placeholder="__('quran_classes.select')"
                                   :options="$branches"
                                   required />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="status"
                                   :label="__('quran_classes.status')"
                                   :selected="$quranClass?->status?->value ?? App\Enums\Status::Active->value"
                                   :options="collect(App\Enums\Status::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                                   required />
                </div>
            </div>
        </x-card>

        <x-card :title="__('quran_classes.schedule')" icon="bi-clock">
            <p class="fs-sm text-soft mt-n1 mb-3">{{ __('quran_classes.schedule_hint') }}</p>
            <div class="row">
                <div class="col-12 col-md-4">
                    <x-form.input name="start_time"
                                  type="time"
                                  :label="__('quran_classes.start_time')"
                                  :value="$quranClass?->start_time ? \Carbon\Carbon::parse($quranClass->start_time)->format('H:i') : null"
                                  optional />
                </div>
                <div class="col-12 col-md-4">
                    <x-form.input name="end_time"
                                  type="time"
                                  :label="__('quran_classes.end_time')"
                                  :value="$quranClass?->end_time ? \Carbon\Carbon::parse($quranClass->end_time)->format('H:i') : null"
                                  optional />
                </div>
                <div class="col-12 col-md-4">
                    <x-form.input name="max_strength"
                                  type="number"
                                  :label="__('quran_classes.max_strength')"
                                  :value="$quranClass?->max_strength ?? 25"
                                  :help="__('quran_classes.max_strength_help')"
                                  min="1" max="999" step="1"
                                  inputmode="numeric"
                                  required />
                </div>
            </div>
        </x-card>
    </div>

    <div class="col-12 col-xl-4">
        <x-card :title="__('quran_classes.about_title')" icon="bi-info-circle">
            <ul class="stack-sm list-unstyled mb-0 fs-md text-soft">
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_classes.about_point_1') }}</li>
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_classes.about_point_2') }}</li>
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_classes.about_point_3') }}</li>
            </ul>

            @if ($quranClass)
                <a href="{{ route('quran-classes.members.index', $quranClass) }}"
                   class="btn btn-outline-secondary btn-sm w-100 mt-3">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span>{{ __('quran_classes.manage_members') }}</span>
                </a>
            @endif
        </x-card>
    </div>
</div>

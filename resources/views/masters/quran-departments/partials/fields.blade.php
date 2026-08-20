<div class="col-12 col-md-6">
    <x-form.input name="department_name" :label="__('masters.department_name')" :value="$record?->department_name" required autocomplete="off" />
</div>

<div class="col-6 col-md-3">
    <x-form.input name="display_order" type="number" :label="__('masters.display_order')"
                  :value="$record?->display_order ?? 0" :help="__('masters.display_order_help')"
                  min="0" step="1" inputmode="numeric" required />
</div>

<div class="col-6 col-md-3">
    @include('masters.partials.status-field', ['record' => $record])
</div>

<div class="col-12">
    <x-form.textarea name="description" :label="__('masters.description')" :value="$record?->description" rows="2" optional />
</div>

<div class="col-12">
    <hr class="my-2">

    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
        <div>
            <h3 class="fs-md fw-semibold mb-0">{{ __('masters.progress_fields_schema') }}</h3>
            <p class="fs-sm text-soft mb-0">{{ __('masters.progress_fields_schema_help') }}</p>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" data-progress-field-add>
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>{{ __('masters.add_field') }}</span>
        </button>
    </div>

    @php($progressFields = old('progress_fields_schema', $record?->progress_fields_schema ?? []))

    <div data-progress-fields-rows>
        @foreach ($progressFields as $index => $field)
            @include('masters.quran-departments.partials.progress-field-row', ['index' => $index, 'field' => $field])
        @endforeach
    </div>

    <p class="fs-sm text-subtle mb-0" data-progress-fields-empty @if (! empty($progressFields)) hidden @endif>
        {{ __('masters.no_progress_fields') }}
    </p>

    <template data-progress-field-template>
        @include('masters.quran-departments.partials.progress-field-row', ['index' => '__INDEX__', 'field' => []])
    </template>
</div>

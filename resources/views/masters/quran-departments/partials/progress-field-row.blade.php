{{--
    One row of the Progress Fields builder. Used both for existing/old rows
    (via the loop in fields.blade.php) and as the hidden <template> the
    "Add Field" button clones — for the template, $index is the literal
    string '__INDEX__', replaced by JS with a real index before the clone is
    appended, and $field is empty.

    $field here is always already-normalized (either the model's stored
    `progress_fields_schema` array, or old('progress_fields_schema') after a
    failed submission — both went through StoreQuranDepartmentRequest's
    prepareForValidation(), so `options` is always an array, never a raw
    comma string).

    Layout is deliberately spread across a few short rows rather than one
    crowded line — six-plus inputs squeezed into a single Bootstrap row left
    the narrowest columns (Required, in particular) too tight to hold their
    own label without wrapping mid-word.
--}}
@php
    $field = $field ?? [];
    $type = $field['type'] ?? 'number';
    $options = $field['options'] ?? [];
@endphp
<div class="border rounded p-3 mb-2" data-progress-field-row>
    <div class="row g-2">
        <div class="col-12 col-md-5">
            <label class="form-label fs-sm">{{ __('masters.field_label') }}</label>
            <input type="text" class="form-control form-control-sm" data-progress-field-label
                   name="progress_fields_schema[{{ $index }}][label]"
                   placeholder="{{ __('masters.field_label_placeholder') }}"
                   value="{{ $field['label'] ?? '' }}">
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fs-sm">{{ __('masters.field_key') }}</label>
            <input type="text" class="form-control form-control-sm" data-progress-field-key
                   name="progress_fields_schema[{{ $index }}][key]"
                   value="{{ $field['key'] ?? '' }}">
            <div class="form-text">{{ __('masters.field_key_help') }}</div>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label fs-sm">{{ __('masters.field_type') }}</label>
            <select class="form-select form-select-sm" data-progress-field-type
                    name="progress_fields_schema[{{ $index }}][type]">
                <option value="number" @selected($type === 'number')>{{ __('masters.field_type_number') }}</option>
                <option value="select" @selected($type === 'select')>{{ __('masters.field_type_select') }}</option>
                <option value="text" @selected($type === 'text')>{{ __('masters.field_type_text') }}</option>
            </select>
        </div>
    </div>

    <div class="row g-2 mt-1" data-progress-field-number-group>
        <div class="col-6 col-md-3">
            <label class="form-label fs-sm">{{ __('masters.min') }}</label>
            <input type="number" class="form-control form-control-sm"
                   name="progress_fields_schema[{{ $index }}][min]"
                   placeholder="{{ __('masters.min_placeholder') }}"
                   value="{{ $field['min'] ?? '' }}">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label fs-sm">{{ __('masters.max') }}</label>
            <input type="number" class="form-control form-control-sm"
                   name="progress_fields_schema[{{ $index }}][max]"
                   placeholder="{{ __('masters.max_placeholder') }}"
                   value="{{ $field['max'] ?? '' }}">
        </div>
    </div>

    <div class="row g-2 mt-1" data-progress-field-select-group hidden>
        <div class="col-12">
            <label class="form-label fs-sm">{{ __('masters.options') }}</label>
            <input type="text" class="form-control form-control-sm"
                   name="progress_fields_schema[{{ $index }}][options]"
                   placeholder="{{ __('masters.options_placeholder') }}"
                   value="{{ implode(', ', $options) }}">
            <div class="form-text">{{ __('masters.options_help') }}</div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" value="1"
                   name="progress_fields_schema[{{ $index }}][required]"
                   id="progress_field_required_{{ $index }}"
                   @checked($field['required'] ?? false)>
            <label class="form-check-label fs-sm" for="progress_field_required_{{ $index }}">{{ __('masters.required') }}</label>
        </div>

        <button type="button" class="btn btn-outline-danger btn-sm" data-progress-field-remove>
            <i class="bi bi-trash" aria-hidden="true"></i>
            <span>{{ __('masters.remove_field') }}</span>
        </button>
    </div>
</div>

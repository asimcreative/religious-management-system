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
--}}
@php
    $field = $field ?? [];
    $type = $field['type'] ?? 'number';
    $options = $field['options'] ?? [];
@endphp
<div class="border rounded p-3 mb-2" data-progress-field-row>
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label fs-sm">{{ __('masters.field_label') }}</label>
            <input type="text" class="form-control form-control-sm" data-progress-field-label
                   name="progress_fields_schema[{{ $index }}][label]"
                   value="{{ $field['label'] ?? '' }}">
        </div>

        <div class="col-12 col-md-2">
            <label class="form-label fs-sm">{{ __('masters.field_key') }}</label>
            <input type="text" class="form-control form-control-sm" data-progress-field-key
                   name="progress_fields_schema[{{ $index }}][key]"
                   value="{{ $field['key'] ?? '' }}">
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label fs-sm">{{ __('masters.field_type') }}</label>
            <select class="form-select form-select-sm" data-progress-field-type
                    name="progress_fields_schema[{{ $index }}][type]">
                <option value="number" @selected($type === 'number')>{{ __('masters.field_type_number') }}</option>
                <option value="select" @selected($type === 'select')>{{ __('masters.field_type_select') }}</option>
                <option value="text" @selected($type === 'text')>{{ __('masters.field_type_text') }}</option>
            </select>
        </div>

        <div class="col-12 col-md-3" data-progress-field-number-group>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label fs-sm">{{ __('masters.min') }}</label>
                    <input type="number" class="form-control form-control-sm"
                           name="progress_fields_schema[{{ $index }}][min]"
                           value="{{ $field['min'] ?? '' }}">
                </div>
                <div class="col-6">
                    <label class="form-label fs-sm">{{ __('masters.max') }}</label>
                    <input type="number" class="form-control form-control-sm"
                           name="progress_fields_schema[{{ $index }}][max]"
                           value="{{ $field['max'] ?? '' }}">
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3" data-progress-field-select-group hidden>
            <label class="form-label fs-sm">{{ __('masters.options') }}</label>
            <input type="text" class="form-control form-control-sm"
                   name="progress_fields_schema[{{ $index }}][options]"
                   placeholder="{{ __('masters.options_placeholder') }}"
                   value="{{ implode(', ', $options) }}">
            <div class="form-text">{{ __('masters.options_help') }}</div>
        </div>

        <div class="col-6 col-md-1 d-flex align-items-center pb-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1"
                       name="progress_fields_schema[{{ $index }}][required]"
                       id="progress_field_required_{{ $index }}"
                       @checked($field['required'] ?? false)>
                <label class="form-check-label fs-sm" for="progress_field_required_{{ $index }}">{{ __('masters.required') }}</label>
            </div>
        </div>

        <div class="col-6 col-md-1 text-end pb-2">
            <button type="button" class="btn btn-outline-danger btn-sm" data-progress-field-remove
                    aria-label="{{ __('masters.remove_field') }}">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

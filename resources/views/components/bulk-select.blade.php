{{--
    Row selection checkbox.

    The checkbox lives in the table but belongs to the bulk form rendered by
    the toolbar, via the HTML `form` attribute. That is what lets bulk actions
    work with JavaScript switched off: the browser submits the ticked boxes
    even though the form element is elsewhere in the document.

    Usage:
        <x-bulk-select resource="employees" :id="$employee->id" :label="$employee->employee_name" />
        <x-bulk-select resource="employees" all />
--}}
@props([
    'resource',
    'id' => null,
    'label' => null,
    'all' => false,
])

@php
    $formId = 'bulk-form-'.\Illuminate\Support\Str::slug($resource);
@endphp

@if ($all)
    <input type="checkbox"
           class="form-check-input"
           form="{{ $formId }}"
           data-row-select-all
           aria-label="{{ __('ui.select_all') }}">
@else
    <input type="checkbox"
           class="form-check-input"
           name="ids[]"
           value="{{ $id }}"
           form="{{ $formId }}"
           data-row-select
           aria-label="{{ $label ? __('ui.select_row', ['record' => $label]) : __('ui.select') }}">
@endif

{{--
    Shared teacher form fields (create + edit).

    On edit the currently linked employee is not part of `$employees`
    (the query excludes employees that already have a teacher record), so it
    is added back as the pre-selected option.
--}}
@php
    // Keep this as one block. Blade's raw-block compiler mis-parses a
    // single-expression directive placed immediately before a multi-line one.
    $teacher = $teacher ?? null;

    $selectedBranchIds = array_map(
        'intval',
        old('branch_ids', $teacher ? $teacher->branches->pluck('id')->all() : [])
    );
@endphp

<x-form.error-summary />

<div class="row g-4">
    <div class="col-12 col-xl-8">

        <x-card :title="__('teachers.teacher_info')" icon="bi-mortarboard" class="mb-3">
            <div class="row">
                <div class="col-12 col-md-6">
                    <x-form.input name="teacher_code"
                                  :label="__('teachers.teacher_code')"
                                  :value="$teacher?->teacher_code"
                                  :help="__('teachers.teacher_code_help')"
                                  required
                                  autocomplete="off" />
                </div>

                <div class="col-12 col-md-6">
                    <x-form.select name="status"
                                   :label="__('teachers.status')"
                                   :selected="$teacher?->status?->value ?? App\Enums\Status::Active->value"
                                   :options="collect(App\Enums\Status::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                                   required />
                </div>

                <div class="col-12">
                    <x-form.select name="employee_id"
                                   :label="__('teachers.select_employee')"
                                   :selected="$teacher?->employee_id"
                                   :placeholder="__('teachers.select')"
                                   :help="__('teachers.select_employee_help')"
                                   data-searchable-select
                                   required>
                        @if ($teacher?->employee)
                            <option value="{{ $teacher->employee_id }}"
                                @selected((int) old('employee_id', $teacher->employee_id) === (int) $teacher->employee_id)>
                                {{ $teacher->employee->employee_code }} — {{ $teacher->employee->employee_name }}
                            </option>
                        @endif

                        @foreach ($employees as $employee)
                            @continue($teacher && $employee->id === $teacher->employee_id)
                            <option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>
                                {{ $employee->employee_code }} — {{ $employee->employee_name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>
            </div>
        </x-card>

        <x-card :title="__('teachers.assign_branches')" icon="bi-building">
            <x-slot:actions>
                <span class="badge-soft badge-soft-primary"
                      data-count-for="input[name='branch_ids[]']"
                      aria-live="polite">{{ count($selectedBranchIds) }}</span>
            </x-slot:actions>

            <p class="fs-sm text-soft mt-n1 mb-3">{{ __('teachers.assign_branches_hint') }}</p>

            @error('branch_ids')
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-octagon-fill alert__icon" aria-hidden="true"></i>
                    <div class="alert__body">{{ $message }}</div>
                </div>
            @enderror

            @if (count($branches) === 0)
                <x-empty-state size="sm"
                               icon="bi-building"
                               :title="__('teachers.no_branches_available')"
                               :text="__('teachers.no_branches_available_text')" />
            @else
                <fieldset>
                    <legend class="visually-hidden">{{ __('teachers.assign_branches') }}</legend>
                    <div class="row g-2">
                        @foreach ($branches as $id => $name)
                            <div class="col-12 col-sm-6 col-lg-4">
                                <label class="check-card" for="branch_{{ $id }}">
                                    <input class="form-check-input" type="checkbox" name="branch_ids[]"
                                           value="{{ $id }}" id="branch_{{ $id }}"
                                           @checked(in_array((int) $id, $selectedBranchIds, true))>
                                    <span class="check-card__label">{{ $name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endif
        </x-card>
    </div>

    <div class="col-12 col-xl-4">
        <x-card :title="__('teachers.notes')" icon="bi-sticky">
            <x-form.textarea name="notes"
                             :value="$teacher?->notes"
                             rows="6"
                             :placeholder="__('teachers.notes_placeholder')"
                             class="mb-0" />
        </x-card>
    </div>
</div>

{{--
    Shared employee form fields (create + edit).

    One source of truth for both screens — adding a field, a hint or a
    validation affordance happens once. `$employee` is null on create.
--}}
@php($employee = $employee ?? null)

<x-form.error-summary />

<div class="row g-4">
    <div class="col-12 col-xl-8">

        {{-- ── Personal ─────────────────────────────────────────────── --}}
        <x-card :title="__('employees.personal_info')" icon="bi-person-badge" class="mb-3">
            <div class="row">
                <div class="col-12 col-md-6">
                    <x-form.input name="employee_code"
                                  :label="__('employees.employee_code')"
                                  :value="$employee?->employee_code"
                                  :help="__('employees.employee_code_help')"
                                  required
                                  autocomplete="off" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.input name="employee_name"
                                  :label="__('employees.employee_name')"
                                  :value="$employee?->employee_name"
                                  required
                                  autocomplete="name" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.input name="cnic"
                                  :label="__('employees.cnic')"
                                  :value="$employee?->cnic"
                                  placeholder="XXXXX-XXXXXXX-X"
                                  :help="__('employees.cnic_help')"
                                  optional
                                  inputmode="numeric" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.input name="mobile"
                                  type="tel"
                                  :label="__('employees.mobile')"
                                  :value="$employee?->mobile"
                                  icon="bi-telephone"
                                  optional
                                  inputmode="tel"
                                  autocomplete="tel" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.input name="email"
                                  type="email"
                                  :label="__('employees.email')"
                                  :value="$employee?->email"
                                  icon="bi-envelope"
                                  optional
                                  inputmode="email"
                                  autocomplete="email" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.input name="dob"
                                  type="date"
                                  :label="__('employees.dob')"
                                  :value="$employee?->dob?->format('Y-m-d')"
                                  optional
                                  max="{{ now()->format('Y-m-d') }}" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="gender"
                                   :label="__('employees.gender')"
                                   :selected="$employee?->gender"
                                   :placeholder="__('employees.select')"
                                   :options="['male' => __('employees.male'), 'female' => __('employees.female')]"
                                   optional />
                </div>
            </div>
        </x-card>

        {{-- ── Organization ─────────────────────────────────────────── --}}
        <x-card :title="__('employees.organization_info')" icon="bi-diagram-3" class="mb-3">
            <div class="row">
                <div class="col-12 col-md-6">
                    <x-form.select name="branch_id"
                                   :label="__('employees.branch')"
                                   :selected="$employee?->branch_id"
                                   :placeholder="__('employees.select')"
                                   :options="$branches"
                                   required />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="department_id"
                                   :label="__('employees.department')"
                                   :selected="$employee?->department_id"
                                   :placeholder="__('employees.select')"
                                   :options="$departments"
                                   required />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="designation_id"
                                   :label="__('employees.designation')"
                                   :selected="$employee?->designation_id"
                                   :placeholder="__('employees.select')"
                                   :options="$designations"
                                   required />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="employment_status"
                                   :label="__('employees.status')"
                                   :selected="$employee?->employment_status?->value ?? App\Enums\Status::Active->value"
                                   :options="collect(App\Enums\Status::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                                   required />
                </div>
            </div>
        </x-card>

        {{-- ── Religious ────────────────────────────────────────────── --}}
        <x-card :title="__('employees.religious_info')"
                icon="bi-book"
                class="mb-3">
            <p class="fs-sm text-soft mt-n1 mb-3">{{ __('employees.religious_info_hint') }}</p>
            <div class="row">
                <div class="col-12 col-md-6">
                    <x-form.select name="quran_department_id"
                                   :label="__('employees.quran_department')"
                                   :selected="$employee?->quran_department_id"
                                   :placeholder="__('employees.select')"
                                   :options="$quranDepartments"
                                   optional />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="quran_status_id"
                                   :label="__('employees.quran_status')"
                                   :selected="$employee?->quran_status_id"
                                   :placeholder="__('employees.select')"
                                   :options="$quranStatuses"
                                   optional />
                </div>
            </div>
        </x-card>
    </div>

    {{-- ── Side column ──────────────────────────────────────────────── --}}
    <div class="col-12 col-xl-4">
        <x-card :title="__('employees.photo')" icon="bi-camera" class="mb-3">
            <div class="text-center mb-3">
                @if ($employee?->photo)
                    <img src="{{ route('employees.photo', $employee) }}"
                         alt="{{ $employee->employee_name }}"
                         class="radius-lg"
                         style="width:110px;height:110px;object-fit:cover;"
                         loading="lazy" decoding="async">
                @else
                    <x-avatar :name="$employee?->employee_name ?? ''" size="xl" class="mx-auto" />
                @endif
            </div>

            <label for="photo" class="form-label">
                {{ __('employees.upload_photo') }}<span class="opt">{{ __('ui.optional') }}</span>
            </label>
            <input type="file" name="photo" id="photo" accept="image/*"
                   class="form-control @error('photo') is-invalid @enderror"
                   aria-describedby="photo-help">
            <div class="form-text" id="photo-help">
                {{ $employee?->photo ? __('employees.current_photo_exists') : __('employees.photo_help') }}
            </div>
            @error('photo')
                <div class="invalid-feedback d-flex">{{ $message }}</div>
            @enderror
        </x-card>

        <x-card :title="__('employees.notes')" icon="bi-sticky">
            <x-form.textarea name="notes"
                             :value="$employee?->notes"
                             rows="5"
                             :placeholder="__('employees.notes_placeholder')"
                             class="mb-0" />
        </x-card>
    </div>
</div>

{{-- Shared Jamaat form fields (create + edit). --}}
@php($jamaat = $jamaat ?? null)

<x-form.error-summary />

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <x-card :title="__('jamaats.jamaat_info')" icon="bi-people-fill" class="mb-3">
            <div class="row">
                <div class="col-12 col-md-4">
                    <x-form.input name="jamaat_number"
                                  :label="__('jamaats.jamaat_number')"
                                  :value="$jamaat?->jamaat_number"
                                  :help="__('jamaats.jamaat_number_help')"
                                  required
                                  autocomplete="off" />
                </div>
                <div class="col-12 col-md-8">
                    <x-form.input name="jamaat_name"
                                  :label="__('jamaats.jamaat_name')"
                                  :value="$jamaat?->jamaat_name"
                                  required
                                  autocomplete="off" />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="branch_id"
                                   :label="__('jamaats.branch')"
                                   :selected="$jamaat?->branch_id"
                                   :placeholder="__('jamaats.select')"
                                   :options="$branches"
                                   required />
                </div>
                <div class="col-12 col-md-6">
                    <x-form.select name="status"
                                   :label="__('jamaats.status')"
                                   :selected="$jamaat?->status?->value ?? App\Enums\Status::Active->value"
                                   :options="collect(App\Enums\Status::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()"
                                   required />
                </div>
            </div>
        </x-card>

        <x-card :title="__('jamaats.leadership')" icon="bi-person-badge">
            <p class="fs-sm text-soft mt-n1 mb-3">{{ __('jamaats.leadership_hint') }}</p>

            <div class="row">
                <div class="col-12 col-md-6">
                    <x-form.select name="leader_id"
                                   :label="__('jamaats.leader')"
                                   :selected="$jamaat?->leader_id"
                                   :placeholder="__('jamaats.select')"
                                   data-searchable-select
                                   required>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}"
                                @selected((int) old('leader_id', $jamaat?->leader_id ?? 0) === $employee->id)>
                                {{ $employee->employee_name }} ({{ $employee->employee_code }})
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div class="col-12 col-md-6">
                    <x-form.select name="vice_leader_id"
                                   :label="__('jamaats.vice_leader')"
                                   :selected="$jamaat?->vice_leader_id"
                                   :placeholder="__('jamaats.select')"
                                   data-searchable-select
                                   optional>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}"
                                @selected((int) old('vice_leader_id', $jamaat?->vice_leader_id ?? 0) === $employee->id)>
                                {{ $employee->employee_name }} ({{ $employee->employee_code }})
                            </option>
                        @endforeach
                    </x-form.select>
                </div>
            </div>
        </x-card>
    </div>

    <div class="col-12 col-xl-4">
        <x-card :title="__('jamaats.about_title')" icon="bi-info-circle">
            <ul class="stack-sm list-unstyled mb-0 fs-md text-soft">
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('jamaats.about_point_1') }}</li>
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('jamaats.about_point_2') }}</li>
                <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('jamaats.about_point_3') }}</li>
            </ul>

            @if ($jamaat)
                <a href="{{ route('jamaats.members.index', $jamaat) }}" class="btn btn-outline-secondary btn-sm w-100 mt-3">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span>{{ __('jamaats.manage_members') }}</span>
                </a>
            @endif
        </x-card>
    </div>
</div>

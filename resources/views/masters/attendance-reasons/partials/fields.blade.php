<div class="col-12 col-md-6">
    <x-form.input name="reason_name" :label="__('masters.reason_name')" :value="$record?->reason_name" required autocomplete="off" />
</div>

<div class="col-6 col-md-3">
    <label for="color" class="form-label">
        {{ __('masters.color') }}<span class="opt">{{ __('ui.optional') }}</span>
    </label>
    <input type="color" name="color" id="color"
           class="form-control form-control-color w-100 @error('color') is-invalid @enderror"
           value="{{ old('color', $record?->color ?? '#64748B') }}"
           aria-describedby="color-help">
    <div class="form-text" id="color-help">{{ __('masters.color_help') }}</div>
    @error('color') <div class="invalid-feedback d-flex">{{ $message }}</div> @enderror
</div>

<div class="col-6 col-md-3">
    <x-form.input name="icon" :label="__('masters.icon')" :value="$record?->icon"
                  :help="__('masters.icon_help')" placeholder="bi-calendar-x" optional />
</div>

<div class="col-12">
    <p class="form-label mb-2">{{ __('masters.counting_rules') }}</p>
    <div class="row g-2">
        <div class="col-12 col-sm-6">
            {{-- Hidden input keeps the unchecked state explicit for the server. --}}
            <input type="hidden" name="counts_as_absent" value="0">
            <label class="check-card" for="counts_as_absent">
                <input class="form-check-input" type="checkbox" name="counts_as_absent" value="1" id="counts_as_absent"
                       @checked((bool) old('counts_as_absent', $record?->counts_as_absent))>
                <span class="check-card__label">
                    {{ __('masters.counts_as_absent') }}
                    <span class="d-block fs-xs text-soft">{{ __('masters.counts_as_absent_help') }}</span>
                </span>
            </label>
        </div>

        <div class="col-12 col-sm-6">
            <input type="hidden" name="counts_as_leave" value="0">
            <label class="check-card" for="counts_as_leave">
                <input class="form-check-input" type="checkbox" name="counts_as_leave" value="1" id="counts_as_leave"
                       @checked((bool) old('counts_as_leave', $record?->counts_as_leave))>
                <span class="check-card__label">
                    {{ __('masters.counts_as_leave') }}
                    <span class="d-block fs-xs text-soft">{{ __('masters.counts_as_leave_help') }}</span>
                </span>
            </label>
        </div>
    </div>
</div>

<div class="col-12 col-md-5 mt-3">
    @include('masters.partials.status-field', ['record' => $record])
</div>

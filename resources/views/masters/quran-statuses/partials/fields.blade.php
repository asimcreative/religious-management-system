<div class="col-12 col-md-6">
    <x-form.input name="status_name" :label="__('masters.status_name')" :value="$record?->status_name" required autocomplete="off" />
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
    <x-form.input name="display_order" type="number" :label="__('masters.display_order')"
                  :value="$record?->display_order ?? 0" :help="__('masters.display_order_help')"
                  min="0" step="1" inputmode="numeric" required />
</div>

<div class="col-12 col-md-6">
    <x-form.input name="icon" :label="__('masters.icon')" :value="$record?->icon"
                  :help="__('masters.icon_help')" placeholder="bi-patch-check" optional />
</div>

<div class="col-12 col-md-6">
    @include('masters.partials.status-field', ['record' => $record])
</div>

<div class="col-12">
    <x-form.textarea name="description" :label="__('masters.description')" :value="$record?->description" rows="2" optional />
</div>

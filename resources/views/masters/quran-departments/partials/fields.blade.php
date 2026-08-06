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

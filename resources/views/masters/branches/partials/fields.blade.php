<div class="col-12 col-md-7">
    <x-form.input name="branch_name" :label="__('masters.branch_name')" :value="$record?->branch_name" required autocomplete="off" />
</div>

<div class="col-12 col-md-5">
    <x-form.input name="phone" type="tel" :label="__('masters.phone')" :value="$record?->phone" icon="bi-telephone" optional inputmode="tel" />
</div>

<div class="col-12">
    <x-form.textarea name="address" :label="__('masters.address')" :value="$record?->address" rows="2" optional />
</div>

<div class="col-12 col-md-5">
    @include('masters.partials.status-field', ['record' => $record])
</div>

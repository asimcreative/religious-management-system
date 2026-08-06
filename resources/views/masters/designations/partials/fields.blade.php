<div class="col-12 col-md-7">
    <x-form.input name="designation_name" :label="__('masters.designation_name')" :value="$record?->designation_name" required autocomplete="off" />
</div>

<div class="col-12 col-md-5">
    @include('masters.partials.status-field', ['record' => $record])
</div>

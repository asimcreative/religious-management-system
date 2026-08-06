<div class="col-12 col-md-7">
    <x-form.input name="department_name" :label="__('masters.department_name')" :value="$record?->department_name" required autocomplete="off" />
</div>

<div class="col-12 col-md-5">
    @include('masters.partials.status-field', ['record' => $record])
</div>

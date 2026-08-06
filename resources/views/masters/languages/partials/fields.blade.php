<div class="col-12 col-md-6">
    <x-form.input name="language_name" :label="__('masters.language_name')" :value="$record?->language_name" required autocomplete="off" />
</div>

<div class="col-12 col-md-6">
    <x-form.input name="native_name" :label="__('masters.native_name')" :value="$record?->native_name"
                  :help="__('masters.native_name_help')" optional />
</div>

<div class="col-6 col-md-4">
    <x-form.input name="locale" :label="__('masters.locale')" :value="$record?->locale"
                  :help="__('masters.locale_help')" placeholder="en" maxlength="10" required
                  autocapitalize="off" autocomplete="off" spellcheck="false" />
</div>

<div class="col-6 col-md-4">
    <x-form.select name="direction"
                   :label="__('masters.direction')"
                   :selected="$record?->direction ?? 'ltr'"
                   :options="['ltr' => __('masters.ltr'), 'rtl' => __('masters.rtl')]"
                   required />
</div>

<div class="col-12 col-md-4">
    @include('masters.partials.status-field', ['record' => $record])
</div>

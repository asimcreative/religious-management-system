{{-- Shared create/edit fields for a tenant. $company is null when creating. --}}
@php($company = $company ?? null)

<x-form.section :title="__('companies.details')" icon="bi-buildings">
    <div class="row">
        <div class="col-md-4">
            <x-form.input name="company_code"
                          :label="__('companies.company_code')"
                          :value="$company?->company_code"
                          required
                          icon="bi-hash"
                          :help="__('companies.code_help')" />
        </div>

        <div class="col-md-8">
            <x-form.input name="company_name"
                          :label="__('companies.company_name')"
                          :value="$company?->company_name"
                          required
                          icon="bi-building" />
        </div>

        <div class="col-md-6">
            <x-form.select name="timezone"
                           :label="__('companies.timezone')"
                           :selected="$company?->timezone ?? 'Asia/Karachi'"
                           required
                           :options="collect(DateTimeZone::listIdentifiers())->mapWithKeys(fn ($tz) => [$tz => $tz])->all()"
                           :help="__('companies.timezone_help')" />
        </div>

        <div class="col-md-6">
            <x-form.select name="default_language"
                           :label="__('companies.default_language')"
                           :selected="$company?->default_language ?? 'en'"
                           :options="['en' => __('ui.locale_en'), 'ur' => __('ui.locale_ur')]" />
        </div>
    </div>
</x-form.section>

<x-form.section :title="__('companies.contact')" icon="bi-telephone">
    <div class="row">
        <div class="col-md-6">
            <x-form.input name="email" type="email" :label="__('companies.email')" :value="$company?->email" required icon="bi-envelope" />
        </div>

        <div class="col-md-6">
            <x-form.input name="phone" :label="__('companies.phone')" :value="$company?->phone" optional icon="bi-telephone" />
        </div>

        <div class="col-md-6">
            <x-form.input name="city" :label="__('companies.city')" :value="$company?->city" optional />
        </div>

        <div class="col-md-6">
            <x-form.input name="country" :label="__('companies.country')" :value="$company?->country" optional />
        </div>

        <div class="col-12">
            <x-form.textarea name="address" :label="__('companies.address')" :value="$company?->address" optional rows="2" />
        </div>
    </div>
</x-form.section>

<x-form.section :title="__('companies.subscription')" icon="bi-credit-card">
    <div class="row">
        <div class="col-md-4">
            <x-form.input name="subscription_plan" :label="__('companies.subscription_plan')" :value="$company?->subscription_plan" optional />
        </div>

        <div class="col-md-4">
            <x-form.input name="subscription_expiry"
                          type="date"
                          :label="__('companies.subscription_expiry')"
                          :value="$company?->subscription_expiry?->format('Y-m-d')"
                          optional />
        </div>

        <div class="col-md-4">
            <x-form.select name="status"
                           :label="__('companies.status')"
                           :selected="$company?->status?->value ?? App\Enums\Status::Active->value"
                           required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                            @selected((string) old('status', $company?->status?->value ?? App\Enums\Status::Active->value) === (string) $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </x-form.select>
        </div>
    </div>
</x-form.section>

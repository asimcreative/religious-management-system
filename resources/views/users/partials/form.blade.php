{{--
    Shared create/edit fields for a user account.

    $user is null when creating. The password is required on create and
    optional on edit, where blank means "leave it as it is" — the service
    drops the key rather than writing an empty hash.
--}}
@php
    $user = $user ?? null;
    $mayAssignRoles = $user
        ? auth()->user()->can('assignRoles', $user)
        : auth()->user()->can('permission.assign');
    $currentRoles = old('roles', $user ? $user->roles->pluck('name')->all() : []);
@endphp

<x-form.section :title="__('users.account_details')" icon="bi-person-badge">
    <div class="row">
        <div class="col-md-6">
            <x-form.input name="name"
                          :label="__('users.name')"
                          :value="$user?->name"
                          required
                          icon="bi-person"
                          autocomplete="name" />
        </div>

        <div class="col-md-6">
            <x-form.input name="email"
                          type="email"
                          :label="__('users.email')"
                          :value="$user?->email"
                          required
                          icon="bi-envelope"
                          autocomplete="email" />
        </div>

        <div class="col-md-6">
            <x-form.input name="mobile"
                          :label="__('users.mobile')"
                          :value="$user?->mobile"
                          optional
                          icon="bi-telephone"
                          autocomplete="tel" />
        </div>

        <div class="col-md-6">
            <x-form.select name="language"
                           :label="__('users.language')"
                           :selected="$user?->language ?? 'en'"
                           :options="['en' => __('ui.locale_en'), 'ur' => __('ui.locale_ur')]"
                           :help="__('users.language_help')" />
        </div>
    </div>
</x-form.section>

<x-form.section :title="__('users.password')" icon="bi-key">
    <div class="row">
        <div class="col-md-6">
            <x-form.password name="password"
                             :label="__('users.password')"
                             :required="$user === null"
                             :help="$user ? __('users.password_edit_help') : __('users.password_help')"
                             autocomplete="new-password" />
        </div>

        <div class="col-md-6">
            <x-form.password name="password_confirmation"
                             :label="__('users.confirm_password')"
                             :required="$user === null"
                             :meter="false"
                             autocomplete="new-password" />
        </div>
    </div>
</x-form.section>

<x-form.section :title="__('users.access')" icon="bi-shield-check" :hint="__('users.roles_help')">
    <div class="row">
        <div class="col-md-6">
            <x-form.select name="status"
                           :label="__('users.status')"
                           :selected="$user?->status?->value ?? App\Enums\Status::Active->value"
                           required
                           :help="__('users.status_help')">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                            @selected((string) old('status', $user?->status?->value ?? App\Enums\Status::Active->value) === (string) $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </x-form.select>
        </div>

        <div class="col-md-6">
            <fieldset class="mb-3">
                <legend class="form-label">{{ __('users.roles') }}</legend>

                @if ($roles->isEmpty())
                    <p class="form-text mb-0">{{ __('users.no_roles') }}</p>
                @else
                    <div class="role-picker @error('roles') is-invalid @enderror">
                        @foreach ($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="roles[]" value="{{ $role }}"
                                       id="role-{{ Str::slug($role) }}"
                                       @checked(in_array($role, $currentRoles, true))
                                       @disabled(! $mayAssignRoles)>
                                <label class="form-check-label" for="role-{{ Str::slug($role) }}">{{ $role }}</label>
                            </div>
                        @endforeach
                    </div>
                @endif

                @error('roles')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                @error('roles.*')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </fieldset>
        </div>
    </div>
</x-form.section>

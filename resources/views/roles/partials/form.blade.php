{{--
    Role name plus the permission grid.

    Permissions are grouped by module because the flat list runs to well over a
    hundred checkboxes, which is unreadable and invites people to tick
    everything. Each group gets its own select-all so granting a whole module
    is one click rather than twelve.
--}}
@php
    $role = $role ?? null;
    $isProtected = $isProtected ?? false;
    $current = old('permissions', $role ? $role->permissions->pluck('name')->all() : []);
    $actor = auth()->user();
@endphp

<x-form.section :title="__('roles.role')" icon="bi-shield-lock">
    <x-form.input name="name"
                  :label="__('roles.name')"
                  :value="$role?->name"
                  required
                  icon="bi-tag"
                  :help="$isProtected ? __('roles.protected_help') : __('roles.name_help')"
                  :readonly="$isProtected" />
</x-form.section>

<x-form.section :title="__('roles.permissions')" icon="bi-key" :hint="__('roles.permissions_help')">
    @error('permissions')
        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
    @enderror

    <div class="permission-grid">
        @foreach ($groups as $module => $permissions)
            @php
                // A permission the actor does not hold cannot be granted by
                // them, so it is shown disabled rather than hidden — hiding it
                // would make the screen look different for different people
                // with no explanation.
                $grantable = array_filter($permissions, fn ($permission) => $actor->can($permission->name));
            @endphp

            <fieldset class="permission-group">
                <legend class="permission-group__title">
                    {{ Str::headline(str_replace('.', ' ', $module)) }}
                    @if ($grantable !== [])
                        <button type="button" class="btn btn-ghost btn-sm permission-group__toggle"
                                data-permission-toggle>{{ __('roles.select_all') }}</button>
                    @endif
                </legend>

                @foreach ($permissions as $permission)
                    @php($mayGrant = $actor->can($permission->name))

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="permissions[]" value="{{ $permission->name }}"
                               id="perm-{{ Str::slug($permission->name) }}"
                               @checked(in_array($permission->name, $current, true))
                               @disabled(! $mayGrant)>
                        <label class="form-check-label" for="perm-{{ Str::slug($permission->name) }}">
                            {{ Str::headline(Str::afterLast($permission->name, '.')) }}
                            <span class="permission-name">{{ $permission->name }}</span>
                        </label>
                    </div>
                @endforeach
            </fieldset>
        @endforeach
    </div>
</x-form.section>

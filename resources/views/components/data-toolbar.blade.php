{{--
    The standard list toolbar.

    Every module's list screen gets the same actions in the same order, driven
    by its transfer definition: adding a module means adding a definition, not
    copying markup. Each button is gated by that module's own permission, so a
    user only ever sees what they may actually do.

    Usage:
        <x-data-toolbar resource="employees"
                        :create-route="route('employees.create')"
                        :create-model="App\Models\Employee::class"
                        :create-label="__('employees.add_new')"
                        :filters="request()->only($filterKeys)" />
--}}
@props([
    'resource',
    'createRoute' => null,
    'createModel' => null,
    'createLabel' => null,
    'filters' => [],
    'selectable' => false,
])

@php
    $registry = app(App\Support\DataTransfer\ResourceRegistry::class);
    $definition = $registry->has($resource) ? $registry->get($resource) : null;
@endphp

@if ($definition)
    @php
        $user = auth()->user();

        // A platform account looking inside a company may not change anything.
        // The write permissions are already denied, which removes Create and
        // Import on their own; these two are gated on reading, so they need
        // saying explicitly rather than offering buttons that cannot work.
        $readOnly = App\Support\Impersonation::isReadOnly();

        $allows = static function (?string $permission) use ($user): bool {
            return $permission === null || (bool) $user?->can($permission);
        };

        $mayImport = $definition->supportsImport() && $allows($definition->permissionFor('import'));
        $mayExport = $definition->supportsExport() && $allows($definition->permissionFor('export'));
        $maySample = $definition->supportsImport() && $allows($definition->permissionFor('sample'));

        // Only declared filters travel with an export, so the file matches the
        // list the user is looking at and nothing else rides along.
        $activeFilters = array_filter(
            array_intersect_key($filters, array_flip($definition->filterKeys())),
            static fn ($value) => $value !== null && $value !== '',
        );

        $exportUrl = static fn (string $format, string $scope, array $extra = []) => route('data.export', array_merge(
            ['resource' => $definition->key(), 'format' => $format, 'scope' => $scope],
            $scope === 'all' ? [] : $activeFilters,
            $extra,
        ));

        $scopes = [
            App\Support\DataTransfer\ExportScope::Filtered,
            App\Support\DataTransfer\ExportScope::Page,
            App\Support\DataTransfer\ExportScope::All,
        ];

        $pageExtras = ['page' => request('page', 1), 'per_page' => request('per_page', 25)];
        $modalId = 'importModal-'.\Illuminate\Support\Str::slug($definition->key());

        // Selection is offered only where the module accepts it and the caller
        // could actually act on a selection.
        $selectable = $selectable
            && ! $readOnly
            && $definition->supportsBulkActions()
            && $user?->can('viewAny', $definition->modelClass());

        $statusColumn = $definition->statusColumn();

        $savedFilters = $user && ! $readOnly
            ? App\Models\SavedFilter::query()
                ->forResource($definition->key())
                ->ownedBy($user->id)
                ->orderBy('name')
                ->get()
            : collect();
    @endphp

    <div {{ $attributes->merge(['class' => 'data-toolbar no-print']) }}
         role="group" aria-label="{{ __('data_transfer.toolbar') }}">

        {{-- Primary action --}}
        @if ($createRoute)
            @can('create', $createModel ?? $definition->modelClass())
                <a href="{{ $createRoute }}" class="btn btn-primary btn-sm"
                   aria-label="{{ $createLabel ?? __('data_transfer.add_new') }}">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>{{ $createLabel ?? __('data_transfer.add_new') }}</span>
                </a>
            @endcan
        @endif

        {{-- Data transfer group --}}
        @if ($mayImport || $mayExport || $maySample)
            <div class="data-toolbar__group">
                {{-- The visible label is hidden below sm to keep the toolbar on
                     one line, so each button carries an aria-label of its own.
                     Without it the button would lose its accessible name at
                     exactly the width where it is hardest to identify. --}}
                @if ($mayImport)
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
                            aria-label="{{ __('data_transfer.import') }}">
                        <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>
                        <span>{{ __('data_transfer.import') }}</span>
                    </button>
                @endif

                @if ($mayExport)
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                aria-label="{{ __('data_transfer.export') }}">
                            <i class="bi bi-box-arrow-up" aria-hidden="true"></i>
                            <span>{{ __('data_transfer.export') }}</span>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end data-toolbar__export">
                            <p class="dropdown-header">{{ __('data_transfer.export_scope_hint') }}</p>

                            @foreach (App\Support\DataTransfer\ExportFormat::cases() as $format)
                                <h6 class="dropdown-header data-toolbar__export-format">
                                    <i class="bi {{ $format->icon() }}" aria-hidden="true"></i>
                                    <span>{{ $format->label() }}</span>
                                </h6>

                                @foreach ($scopes as $scope)
                                    <a class="dropdown-item"
                                       href="{{ $exportUrl($format->value, $scope->value, $scope === App\Support\DataTransfer\ExportScope::Page ? $pageExtras : []) }}">
                                        <i class="bi {{ $scope->icon() }}" aria-hidden="true"></i>
                                        <span>{{ $scope->label() }}</span>
                                    </a>
                                @endforeach

                                @if ($selectable)
                                    <button type="button" class="dropdown-item" disabled
                                            data-export-selected="{{ $exportUrl($format->value, 'selected') }}">
                                        <i class="bi bi-check2-square" aria-hidden="true"></i>
                                        <span>{{ __('data_transfer.scopes.selected') }}</span>
                                    </button>
                                @endif

                                @if (! $loop->last)
                                    <div class="dropdown-divider"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($maySample)
                    <a href="{{ route('data.sample', ['resource' => $definition->key()]) }}"
                       class="btn btn-outline-secondary btn-sm"
                       data-bs-toggle="tooltip" title="{{ __('data_transfer.download_sample') }}"
                       aria-label="{{ __('data_transfer.download_sample') }}">
                        <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                        <span class="d-none d-md-inline">{{ __('data_transfer.download_sample') }}</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- View tools --}}
        <div class="data-toolbar__group">
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-print
                    data-bs-toggle="tooltip" title="{{ __('data_transfer.print') }}"
                    aria-label="{{ __('data_transfer.print') }}">
                <i class="bi bi-printer" aria-hidden="true"></i>
            </button>

            {{-- Copies the table as it is displayed, so hidden columns stay out --}}
            <button type="button" class="btn btn-ghost btn-sm btn-icon" data-copy-table
                    data-copied-message="{{ __('data_transfer.copied', ['count' => ':count']) }}"
                    data-failed-message="{{ __('data_transfer.copy_failed') }}"
                    data-bs-toggle="tooltip" title="{{ __('data_transfer.copy') }}"
                    aria-label="{{ __('data_transfer.copy') }}">
                <i class="bi bi-clipboard" aria-hidden="true"></i>
            </button>

            {{-- Column visibility is read from the rendered table headings, so
                 it needs no per-module configuration and no page reload --}}
            <div class="dropdown">
                <button type="button" class="btn btn-ghost btn-sm btn-icon" data-bs-toggle="dropdown"
                        data-bs-auto-close="outside" aria-expanded="false"
                        data-columns-button
                        aria-label="{{ __('data_transfer.columns') }}">
                    <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end data-toolbar__columns"
                     data-column-toggle data-storage-key="rams.columns.{{ $definition->key() }}">
                    <p class="dropdown-header">{{ __('data_transfer.columns_hint') }}</p>
                    <div data-column-list></div>
                    <div class="dropdown-divider"></div>
                    <button type="button" class="dropdown-item" data-column-reset>
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        <span>{{ __('data_transfer.columns_reset') }}</span>
                    </button>
                </div>
            </div>

            <a href="{{ request()->fullUrl() }}" class="btn btn-ghost btn-sm btn-icon"
               data-bs-toggle="tooltip" title="{{ __('data_transfer.refresh') }}"
               aria-label="{{ __('data_transfer.refresh') }}">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            </a>

            <div class="dropdown">
                <button type="button" class="btn btn-ghost btn-sm btn-icon" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="{{ __('data_transfer.more_actions') }}">
                    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end data-toolbar__overflow">
                    {{-- Saved filters ------------------------------------- --}}
                    @unless ($readOnly)
                    <li><h6 class="dropdown-header">{{ __('data_transfer.saved_filters') }}</h6></li>

                    @forelse ($savedFilters as $saved)
                        <li class="saved-filter">
                            <a class="dropdown-item"
                               href="{{ route($definition->indexRoute() ?? 'dashboard').'?'.$saved->toQueryString() }}">
                                <i class="bi bi-bookmark" aria-hidden="true"></i>
                                <span>{{ $saved->name }}</span>
                            </a>

                            <form method="POST" action="{{ route('data.filters.destroy', $saved) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-icon btn-sm text-danger"
                                        aria-label="{{ __('data_transfer.delete_filter') }}"
                                        data-confirm="{{ __('data_transfer.delete_filter') }}">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </form>
                        </li>
                    @empty
                        <li><p class="dropdown-item-text fs-xs text-soft mb-0">{{ __('data_transfer.no_saved_filters') }}</p></li>
                    @endforelse

                    <li>
                        <form method="POST" action="{{ route('data.filters.store', ['resource' => $definition->key()]) }}"
                              class="saved-filter__form">
                            @csrf
                            <input type="hidden" name="query" value="{{ request()->getQueryString() }}">
                            <label class="visually-hidden" for="save-filter-{{ $definition->key() }}">
                                {{ __('data_transfer.save_filter_name') }}
                            </label>
                            <input type="text" name="name" id="save-filter-{{ $definition->key() }}"
                                   class="form-control form-control-sm" required maxlength="100"
                                   placeholder="{{ __('data_transfer.save_filter_placeholder') }}">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-bookmark-plus" aria-hidden="true"></i>
                                <span>{{ __('data_transfer.save_filter') }}</span>
                            </button>
                        </form>
                    </li>

                    <li><hr class="dropdown-divider"></li>
                    @endunless

                    <li>
                        <a class="dropdown-item" href="{{ route('data.imports.index', ['resource' => $definition->key()]) }}">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <span>{{ __('data_transfer.import_history') }}</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('data.exports.index', ['resource' => $definition->key()]) }}">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <span>{{ __('data_transfer.export_history') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ── Bulk actions ──────────────────────────────────────────────
         Pushed to the end of the document and pinned to the bottom of the
         viewport: the toolbar itself lives in the page header's action slot,
         which is no place for a full-width bar, and a selection bar that
         follows the user down a long list is the more useful thing anyway.

         The form lives here, outside the table; the row checkboxes point at
         it by id, so bulk actions still submit with JavaScript off. --}}
    @if ($selectable)
        @push('modals')
        <div class="bulk-bar d-none" data-selection-bar role="region" aria-label="{{ __('data_transfer.bulk.actions') }}">
            <span class="bulk-bar__count">
                <strong data-selection-count>0</strong>
                <span>{{ __('data_transfer.bulk.selected', ['count' => '']) }}</span>
            </span>

            <form method="POST" action="{{ route('data.bulk', ['resource' => $definition->key()]) }}"
                  id="bulk-form-{{ \Illuminate\Support\Str::slug($definition->key()) }}"
                  class="bulk-bar__form">
                @csrf

                @if ($statusColumn)
                    <label class="visually-hidden" for="bulk-status-{{ $definition->key() }}">
                        {{ __('data_transfer.bulk.status_label') }}
                    </label>
                    <select name="status" id="bulk-status-{{ $definition->key() }}" class="form-select form-select-sm">
                        <option value="">{{ __('data_transfer.bulk.change_status') }}…</option>
                        @foreach ($statusColumn->valueMap() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="submit" name="action" value="status" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                        <span>{{ __('data_transfer.bulk.apply') }}</span>
                    </button>
                @endif

                <button type="submit" name="action" value="delete" class="btn btn-outline-danger btn-sm"
                        data-bulk-delete
                        data-confirm-template="{{ __('data_transfer.bulk.confirm_delete', ['count' => ':count']) }}">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                    <span>{{ __('data_transfer.bulk.delete') }}</span>
                </button>
            </form>

            <button type="button" class="btn btn-ghost btn-sm" data-selection-clear>
                {{ __('data_transfer.bulk.clear') }}
            </button>
        </div>
        @endpush
    @endif

    @if ($mayImport)
        @push('modals')
            <x-data-transfer.import-modal :definition="$definition" :modal-id="$modalId" />
        @endpush
    @endif
@endif

{{--
    Generic master-data list.

    All seven master entities share the same shape: search, a handful of
    columns, a status, and edit/delete actions. Describing the columns as data
    keeps every list identical in behaviour and appearance, and means a change
    to list UX happens once rather than seven times.

    Required:
      $title      string          Plural page title
      $singular   string          Singular entity name (used in buttons/copy)
      $icon       string          Bootstrap Icons class
      $routeBase  string          e.g. 'masters.branches'
      $model      class-string    Policy target for the create button
      $records    LengthAwarePaginator
      $columns    array<array{label:string, value:callable, primary?:bool, html?:bool, class?:string}>
      $nameFor    callable        Record → display name (delete confirmation)

    Optional:
      $intro      string          Short explanation of what the data is for
      $transferResource string    Import/export definition key, e.g. 'branches'.
                                  Supplying it swaps the lone Add button for the
                                  standard toolbar; omitting it leaves the page
                                  exactly as it was.
--}}
@php
    $intro = $intro ?? null;
    $transferResource = $transferResource ?? null;
    $hasSearch = filled(request('search'));
@endphp

<x-page-header :title="$title" :subtitle="$intro" :icon="$icon" :badge="number_format($records->total())">
    <x-slot:actions>
        @if ($transferResource)
            <x-data-toolbar :resource="$transferResource"
                            :create-route="route($routeBase.'.create')"
                            :create-model="$model"
                            :create-label="__('masters.add_new')"
                            :filters="request()->query()"
                            selectable />
        @else
            @can('create', $model)
                <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>{{ __('masters.add_new') }}</span>
                </a>
            @endcan
        @endif
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters
        :active="$hasSearch ? [__('ui.search').': '.request('search') => route($routeBase.'.index')] : []"
        :reset-url="$hasSearch ? route($routeBase.'.index') : null">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('masters.search') }}" value="{{ request('search') }}">
            </div>
        </div>
    </x-filters>

    <x-table sticky :label="$title">
        <thead>
            <tr>
                @if ($transferResource)
                    <th scope="col" class="col-select">
                        <x-bulk-select :resource="$transferResource" all />
                    </th>
                @endif
                <th scope="col" class="col-num">#</th>
                @foreach ($columns as $column)
                    <th scope="col" class="{{ $column['class'] ?? '' }}">{{ $column['label'] }}</th>
                @endforeach
                <th scope="col">{{ __('masters.status') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('masters.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $record)
                <tr>
                    @if ($transferResource)
                        <td class="col-select" data-label="">
                            <x-bulk-select :resource="$transferResource" :id="$record->id" :label="($nameFor)($record)" />
                        </td>
                    @endif
                    <td class="col-num" data-label="#">{{ $records->firstItem() + $loop->index }}</td>

                    @foreach ($columns as $column)
                        @php($value = ($column['value'])($record))
                        <td data-label="{{ $column['label'] }}" class="{{ $column['class'] ?? '' }}">
                            @if (($column['primary'] ?? false))
                                <span class="fw-semibold text-strong">{{ $value }}</span>
                            @elseif (($column['html'] ?? false))
                                {!! $value !!}
                            @elseif (filled($value))
                                {{ $value }}
                            @else
                                <span class="dash">—</span>
                            @endif
                        </td>
                    @endforeach

                    <td data-label="{{ __('masters.status') }}">
                        <x-status-badge :status="$record->status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('masters.actions') }}">
                        <div class="table-actions">
                            @can('update', $record)
                                <a href="{{ route($routeBase.'.edit', $record) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('masters.edit') }}"
                                   aria-label="{{ __('masters.edit') }} — {{ ($nameFor)($record) }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan

                            @can('delete', $record)
                                <x-delete-button :action="route($routeBase.'.destroy', $record)"
                                                 :record="($nameFor)($record)"
                                                 :title="__('masters.delete')" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 3 + ($transferResource ? 1 : 0) }}">
                        @if ($hasSearch)
                            <x-empty-state icon="bi-search" :title="__('ui.no_results_title')" :text="__('ui.no_results_text')">
                                <a href="{{ route($routeBase.'.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state :icon="$icon"
                                           :title="__('masters.empty_title', ['item' => $title])"
                                           :text="__('masters.empty_text', ['item' => Str::lower($singular)])">
                                @can('create', $model)
                                    <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        <span>{{ __('masters.add_new') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$records" />
</x-card>

@can('create', $model)
    <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary btn-fab" aria-label="{{ __('masters.add_new') }}">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
    </a>
@endcan

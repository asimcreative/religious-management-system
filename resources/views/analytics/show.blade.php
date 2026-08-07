@extends('layouts.app')

@section('title', $definition->label().' — '.$result->dimension->label)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.report_center') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.analysis.index') }}">{{ __('analytics.title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $definition->label() }}</li>
@endsection

@section('content')
@php
    // Everything the current view is, as query parameters. Carried into the
    // export links and the "remove this filter" chips so both act on exactly
    // what is on screen rather than on a re-derived guess.
    $current = array_merge($active, [
        'group_by' => $result->dimension->key,
        'then_by' => $result->secondary?->key,
    ]);
    $current = array_filter($current, fn ($value) => $value !== null && $value !== '');

    $exportUrl = fn (string $format) => route('reports.analysis.export', array_merge(
        ['dataset' => $definition->key(), 'format' => $format],
        $current,
    ));

    // A chip removes its own filter and keeps the rest, so narrowing can be
    // undone one step at a time instead of all at once.
    $chips = [];
    foreach ($active as $key => $value) {
        $filter = $definition->filters()[$key];
        $without = array_diff_key($current, [$key => null]);
        $chips[$filter->label.': '.$filter->describe($value)] =
            route('reports.analysis.show', array_merge(['dataset' => $definition->key()], $without));
    }

    $resetUrl = $active === [] ? null : route('reports.analysis.show', [
        'dataset' => $definition->key(),
        'group_by' => $result->dimension->key,
        'then_by' => $result->secondary?->key,
    ]);

    $primary = $result->primaryMeasure();
    $peak = $result->peak();

    $drill = $definition->drillDown();
    $drillKey = $result->dimension->drillFilter;
@endphp

<x-page-header :title="$definition->label()"
               :subtitle="$definition->description()"
               icon="bi-diagram-3"
               :badge="__('analytics.by', ['dimension' => $result->dimension->label])">
    <x-slot:actions>
        <div class="dropdown">
            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false"
                    aria-label="{{ __('data_transfer.export') }}">
                <i class="bi bi-box-arrow-up" aria-hidden="true"></i>
                <span>{{ __('data_transfer.export') }}</span>
            </button>

            <div class="dropdown-menu dropdown-menu-end">
                @foreach (App\Support\DataTransfer\ExportFormat::cases() as $format)
                    <a class="dropdown-item" href="{{ $exportUrl($format->value) }}">
                        <i class="bi {{ $format->icon() }}" aria-hidden="true"></i>
                        <span>{{ $format->label() }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-print
                data-bs-toggle="tooltip" title="{{ __('data_transfer.print') }}"
                aria-label="{{ __('data_transfer.print') }}">
            <i class="bi bi-printer" aria-hidden="true"></i>
        </button>
    </x-slot:actions>
</x-page-header>

{{-- ── Dataset switcher ─────────────────────────────────────────────────── --}}
@if (count($datasets) > 1)
    <nav class="d-flex flex-wrap gap-2 no-print mb-3" aria-label="{{ __('analytics.datasets') }}">
        @foreach ($datasets as $option)
            <a href="{{ route('reports.analysis.show', $option->key()) }}"
               class="btn btn-sm {{ $option->key() === $definition->key() ? 'btn-primary' : 'btn-outline-secondary' }}"
               @if ($option->key() === $definition->key()) aria-current="page" @endif>
                {{ $option->label() }}
            </a>
        @endforeach
    </nav>
@endif

{{-- ── Breakdown + filters ──────────────────────────────────────────────── --}}
<x-card class="mb-3">
    <x-slot:title>{{ __('analytics.build_report') }}</x-slot:title>
    <x-slot:subtitle>{{ __('analytics.build_hint') }}</x-slot:subtitle>

    <x-filters :action="route('reports.analysis.show', $definition->key())"
               :reset-url="$resetUrl"
               :active="$chips">
        {{-- The two breakdown selectors come first: they are the question
             being asked, and the filters below only narrow it. --}}
        <div class="field field--md">
            <label for="group_by" class="form-label fw-semibold">{{ __('analytics.group_by') }}</label>
            <select name="group_by" id="group_by" class="form-select form-select-sm">
                @foreach ($definition->groupedDimensions() as $group => $options)
                    <optgroup label="{{ $group ?: __('analytics.group_other') }}">
                        @foreach ($options as $value => $label)
                            <option value="{{ $value }}" @selected($result->dimension->key === $value)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="then_by" class="form-label fw-semibold">{{ __('analytics.then_by') }}</label>
            <select name="then_by" id="then_by" class="form-select form-select-sm">
                <option value="">{{ __('analytics.then_by_none') }}</option>
                @foreach ($definition->groupedDimensions() as $group => $options)
                    <optgroup label="{{ $group ?: __('analytics.group_other') }}">
                        @foreach ($options as $value => $label)
                            @continue($value === $result->dimension->key)
                            <option value="{{ $value }}" @selected($result->secondary?->key === $value)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        @foreach ($definition->groupedFilters() as $group => $groupFilters)
            @foreach ($groupFilters as $filter)
                <div class="field {{ $filter->width }}">
                    <label for="f-{{ $filter->key }}" class="form-label">
                        {{ $filter->label }}
                        @if ($group)
                            <span class="visually-hidden">({{ $group }})</span>
                        @endif
                    </label>

                    @if ($filter->type->isSelect())
                        <select name="{{ $filter->key }}" id="f-{{ $filter->key }}" class="form-select form-select-sm">
                            <option value="">{{ __('analytics.any') }}</option>
                            @foreach ($filter->options() as $value => $label)
                                <option value="{{ $value }}" @selected(($filters[$filter->key] ?? null) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $filter->type->inputType() }}"
                               name="{{ $filter->key }}"
                               id="f-{{ $filter->key }}"
                               class="form-control form-control-sm"
                               value="{{ $filters[$filter->key] ?? '' }}"
                               @if ($filter->placeholder) placeholder="{{ $filter->placeholder }}" @endif>
                    @endif
                </div>
            @endforeach
        @endforeach
    </x-filters>
</x-card>

{{-- ── Totals ───────────────────────────────────────────────────────────── --}}
@unless ($result->isEmpty())
    <div class="auto-grid auto-grid--sm mb-3">
        @foreach ($result->measures as $measure)
            <x-stat-card
                :label="$measure->label"
                :value="$measure->format->display($result->totals[$measure->key] ?? null)"
                :icon="$measure->primary ? 'bi-check2-circle' : 'bi-hash'"
                :tone="$measure->primary ? 'primary' : 'info'" />
        @endforeach
    </div>
@endunless

{{-- ── Breakdown ────────────────────────────────────────────────────────── --}}
<x-card flush>
    <x-slot:title>{{ __('analytics.by', ['dimension' => $result->dimension->label]) }}</x-slot:title>
    <x-slot:subtitle>
        @if ($result->secondary)
            {{ __('analytics.and_then', ['dimension' => $result->secondary->label]) }}
        @endif
    </x-slot:subtitle>

    @if ($result->truncated)
        <div class="alert alert-warning m-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill alert__icon" aria-hidden="true"></i>
            <div class="alert__body">
                {{ __('analytics.truncated', ['count' => number_format(App\Services\AnalyticsService::MAX_ROWS)]) }}
            </div>
        </div>
    @endif

    <x-table sticky :label="$result->dimension->label">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ $result->dimension->label }}</th>
                @if ($result->secondary)
                    <th scope="col">{{ $result->secondary->label }}</th>
                @endif
                @foreach ($result->measures as $measure)
                    <th scope="col" class="text-end">{{ $measure->label }}</th>
                @endforeach
                @if ($primary)
                    <th scope="col" class="w-cap-sm no-print">
                        <span class="visually-hidden">{{ $primary->label }}</span>
                    </th>
                @endif
            </tr>
        </thead>

        <tbody>
            @forelse ($result->rows as $row)
                <tr>
                    <td class="col-num" data-label="#">{{ $loop->iteration }}</td>

                    <td data-label="{{ $result->dimension->label }}">
                        @if ($drill && $drillKey && $row->key !== null)
                            {{-- Straight from a number to the records behind it.
                                 Only the filters the detail report understands
                                 are carried across; anything else would be
                                 dropped there and quietly widen the result. --}}
                            @php($carry = array_intersect_key($active, array_flip(array_keys($drill['filters']))))
                            <a href="{{ route($drill['route'], array_merge($carry, [$drill['filters'][$drillKey] ?? $drillKey => $row->key])) }}"
                               class="fw-semibold">{{ $row->label }}</a>
                        @else
                            <span class="fw-semibold text-strong">{{ $row->label }}</span>
                        @endif
                    </td>

                    @if ($result->secondary)
                        <td data-label="{{ $result->secondary->label }}">{{ $row->secondaryLabel }}</td>
                    @endif

                    @foreach ($result->measures as $measure)
                        <td class="text-end mono" data-label="{{ $measure->label }}">
                            {{ $measure->format->display($row->measures[$measure->key] ?? null) }}
                        </td>
                    @endforeach

                    @if ($primary)
                        @php($value = (float) ($row->measures[$primary->key] ?? 0))
                        @php($width = $peak > 0 ? round(($value / $peak) * 100) : 0)
                        <td class="no-print" data-label="">
                            {{-- Bars are scaled against the biggest row, not
                                 against the total: with twenty groups every bar
                                 against the total would be a sliver and the
                                 comparison would be invisible. --}}
                            <span class="progress meter-track" role="img"
                                  aria-label="{{ $primary->label }}: {{ $primary->format->display($value) }}">
                                <span class="progress-bar bg-primary" style="width: {{ $width }}%"></span>
                            </span>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 2 + count($result->measures) + ($result->secondary ? 1 : 0) + ($primary ? 1 : 0) }}">
                        <x-empty-state icon="bi-bar-chart"
                                       :title="__('analytics.empty_title')"
                                       :text="__('analytics.empty_text')">
                            @if ($resetUrl)
                                <a href="{{ $resetUrl }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            @endif
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>

        @unless ($result->isEmpty())
            <tfoot>
                <tr class="fw-semibold">
                    <td class="col-num"></td>
                    <td data-label="">{{ __('analytics.total') }}</td>
                    @if ($result->secondary)
                        <td></td>
                    @endif
                    @foreach ($result->measures as $measure)
                        <td class="text-end mono" data-label="{{ $measure->label }}">
                            {{ $measure->format->display($result->totals[$measure->key] ?? null) }}
                        </td>
                    @endforeach
                    @if ($primary)
                        <td class="no-print"></td>
                    @endif
                </tr>
            </tfoot>
        @endunless
    </x-table>
</x-card>

{{-- ── Provenance ───────────────────────────────────────────────────────────
     docs/14_REPORTS_MODULE.md, rule 5: a report must say who produced it,
     when, for which company, what it was filtered to, and how much it covers.
     Printed or exported, it has to explain itself without its URL. --}}
<x-card class="mt-3">
    <x-slot:title>{{ __('analytics.about_this_report') }}</x-slot:title>

    <x-detail-list>
        <x-detail-row :label="__('analytics.meta_company')" :value="$companyName" />
        <x-detail-row :label="__('analytics.meta_generated_by')" :value="auth()->user()?->name" />
        <x-detail-row :label="__('analytics.meta_generated_at')"
                      :value="App\Helpers\TimezoneHelper::formatForDisplay($generatedAt, 'd M Y, H:i')" />
        <x-detail-row :label="__('analytics.meta_breakdown')"
                      :value="$result->secondary
                          ? $result->dimension->label.' → '.$result->secondary->label
                          : $result->dimension->label" />
        <x-detail-row :label="__('analytics.meta_rows')" :value="number_format(count($result->rows))" />
        <x-detail-row :label="__('analytics.meta_filters')">
            @if ($active === [])
                <span class="text-soft">{{ __('analytics.meta_no_filters') }}</span>
            @else
                @foreach ($active as $key => $value)
                    @php($filter = $definition->filters()[$key])
                    <span class="badge-soft badge-soft-neutral">{{ $filter->label }}: {{ $filter->describe($value) }}</span>
                @endforeach
            @endif
        </x-detail-row>
    </x-detail-list>
</x-card>
@endsection

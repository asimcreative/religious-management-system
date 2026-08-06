{{--
    Daily attendance-rate chart.

    Built from CSS-sized columns rather than a charting library: the data is a
    simple 0–100 % series, so a 60 KB dependency would buy nothing. Columns are
    percentage-height divs, which means the chart is responsive, themable and
    prints correctly with no JavaScript at all.

    Accessibility: the visual chart is aria-hidden and paired with a real data
    table exposed only to assistive technology, so the numbers are readable
    rather than being locked inside a picture.
--}}
@props(['trend'])

@php
    $days = $trend['days'] ?? [];

    $series = array_values(array_filter([
        ($trend['has_quran'] ?? false) ? ['key' => 'quran', 'label' => __('quran_attendance.quran_attendance'), 'tone' => 'quran'] : null,
        ($trend['has_salah'] ?? false) ? ['key' => 'salah', 'label' => __('salah_attendance.salah_attendance'), 'tone' => 'salah'] : null,
    ]));

    // A flat run of empty days means nothing was recorded — show the empty
    // state instead of a chart of zeroes pretending to be data.
    $hasData = collect($days)->contains(
        fn ($day) => collect($series)->contains(fn ($s) => ($day[$s['key']]['total'] ?? 0) > 0)
    );
@endphp

@if (empty($series))
    {{-- No attendance module visible to this user: render nothing at all. --}}
@elseif (! $hasData)
    <x-empty-state size="sm"
                   icon="bi-bar-chart-line"
                   :title="__('dashboard.trend_empty_title')"
                   :text="__('dashboard.trend_empty_text')" />
@else
    <div class="trend">
        <div class="trend__legend">
            @foreach ($series as $s)
                <span class="trend__legend-item trend__legend-item--{{ $s['tone'] }}">{{ $s['label'] }}</span>
            @endforeach
            <span class="trend__range">{{ __('dashboard.trend_range', ['days' => count($days)]) }}</span>
        </div>

        <div class="trend__plot" aria-hidden="true">
            <div class="trend__gridlines">
                <span data-value="100%"></span>
                <span data-value="50%"></span>
                <span data-value="0%"></span>
            </div>

            <div class="trend__columns">
                @foreach ($days as $day)
                    @php($date = \Carbon\Carbon::parse($day['date']))
                    <div class="trend__day" title="{{ $date->format('d M Y') }}">
                        <div class="trend__bars">
                            @foreach ($series as $s)
                                @php($rate = $day[$s['key']]['rate'])
                                @if ($rate === null)
                                    <span class="trend__bar trend__bar--empty"></span>
                                @else
                                    <span class="trend__bar trend__bar--{{ $s['tone'] }}"
                                          style="height: {{ max(2, (float) $rate) }}%"></span>
                                @endif
                            @endforeach
                        </div>
                        <span class="trend__label">{{ $date->format('d') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- The same data, readable by screen readers and by search.

             The wrapper carries .visually-hidden rather than the table: a
             table box does not honour the 1px clamp, so the class on the
             <table> left a 370px-wide absolutely-positioned box that pushed
             the document sideways on narrow screens. --}}
        <div class="visually-hidden">
        <table>
            <caption>{{ __('dashboard.trend_title') }}</caption>
            <thead>
                <tr>
                    <th scope="col">{{ __('reports.date') }}</th>
                    @foreach ($series as $s)
                        <th scope="col">{{ $s['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($days as $day)
                    <tr>
                        <th scope="row">{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</th>
                        @foreach ($series as $s)
                            <td>
                                @if ($day[$s['key']]['rate'] === null)
                                    {{ __('dashboard.trend_no_record') }}
                                @else
                                    {{ $day[$s['key']]['rate'] }}% ({{ $day[$s['key']]['present'] }}/{{ $day[$s['key']]['total'] }})
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
@endif

{{--
    A breakdown as a printable document.

    dompdf renders this in isolation — it cannot reach the Vite bundle — so the
    styles are self-contained and stay inside the CSS subset dompdf supports:
    no flexbox, no grid, no custom properties.

    The header carries what docs/14_REPORTS_MODULE.md requires of every report:
    company, who generated it, when, what it was broken down by and what it was
    filtered to. A printed page outlives its URL and has to explain itself.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $definition->label() }} — {{ $result->dimension->label }}</title>
    <style>
        @page { margin: 18mm 12mm 16mm 12mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 0;
        }

        .doc-header { border-bottom: 2px solid #0F766E; padding-bottom: 6px; margin-bottom: 10px; }
        .doc-title { font-size: 15px; font-weight: bold; color: #0F766E; margin: 0 0 2px; }
        .doc-company { font-size: 10px; font-weight: bold; margin: 0 0 2px; }
        .doc-meta { font-size: 8px; color: #6b7280; margin: 0; }

        .filters { font-size: 8px; color: #4b5563; margin-bottom: 8px; }
        .filters strong { color: #1f2937; }

        .notice {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #92400e;
            padding: 5px 7px;
            margin-bottom: 8px;
            font-size: 8px;
        }

        table { width: 100%; border-collapse: collapse; }

        thead th {
            background: #0F766E;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-align: left;
            padding: 5px 4px;
            border: 1px solid #0b5a54;
        }

        tbody td {
            padding: 4px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            word-wrap: break-word;
        }

        tbody tr:nth-child(even) td { background: #f9fafb; }

        tfoot td {
            padding: 5px 4px;
            border: 1px solid #d1d5db;
            background: #f3f4f6;
            font-weight: bold;
        }

        .num { text-align: right; }
    </style>
</head>
<body>

<div class="doc-header">
    <p class="doc-title">{{ $definition->label() }} — {{ __('analytics.by', ['dimension' => $result->dimension->label]) }}</p>

    @if ($companyName)
        <p class="doc-company">{{ $companyName }}</p>
    @endif

    <p class="doc-meta">
        {{ __('analytics.meta_generated_by') }}: {{ $generatedBy ?? '—' }}
        &nbsp;|&nbsp; {{ __('analytics.meta_generated_at') }}: {{ $generatedAt }}
        &nbsp;|&nbsp; {{ __('analytics.meta_rows') }}: {{ number_format(count($result->rows)) }}
    </p>
</div>

@if ($result->truncated)
    <p class="notice">
        {{ __('analytics.truncated', ['count' => number_format(App\Services\AnalyticsService::MAX_ROWS)]) }}
    </p>
@endif

<p class="filters">
    <strong>{{ __('analytics.meta_filters') }}:</strong>
    @if (empty($filters))
        {{ __('analytics.meta_no_filters') }}
    @else
        @foreach ($filters as $label => $value)
            {{ $label }}: {{ $value }}@if (! $loop->last) &nbsp;•&nbsp; @endif
        @endforeach
    @endif
</p>

<table>
    <thead>
        <tr>
            <th>{{ $result->dimension->label }}</th>
            @if ($result->secondary)
                <th>{{ $result->secondary->label }}</th>
            @endif
            @foreach ($result->measures as $measure)
                <th class="num">{{ $measure->label }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @forelse ($result->rows as $row)
            <tr>
                <td>{{ $row->label }}</td>
                @if ($result->secondary)
                    <td>{{ $row->secondaryLabel }}</td>
                @endif
                @foreach ($result->measures as $measure)
                    <td class="num">{{ $measure->format->display($row->measures[$measure->key] ?? null) }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ 1 + count($result->measures) + ($result->secondary ? 1 : 0) }}">
                    {{ __('analytics.empty_title') }}
                </td>
            </tr>
        @endforelse
    </tbody>

    @unless ($result->isEmpty())
        <tfoot>
            <tr>
                <td>{{ __('analytics.total') }}</td>
                @if ($result->secondary)
                    <td></td>
                @endif
                @foreach ($result->measures as $measure)
                    <td class="num">{{ $measure->format->display($result->totals[$measure->key] ?? null) }}</td>
                @endforeach
            </tr>
        </tfoot>
    @endunless
</table>

</body>
</html>

{{--
    Shared PDF layout for every module's export.

    dompdf renders this in isolation — it cannot reach the Vite bundle — so the
    styles below are deliberately self-contained and limited to the CSS subset
    dompdf actually supports (no flexbox, no grid, no custom properties).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Http\Middleware\SetLocale::direction() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $definition->label() }}</title>
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

        .notice {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #92400e;
            padding: 5px 7px;
            margin-bottom: 8px;
            font-size: 8px;
        }

        .filters { font-size: 8px; color: #4b5563; margin-bottom: 8px; }
        .filters strong { color: #1f2937; }

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

        .col-num { width: 26px; text-align: right; color: #6b7280; }
        .empty { color: #9ca3af; }

        .doc-footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            font-size: 7px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 3px;
        }
        .doc-footer .right { float: right; }
    </style>
</head>
<body>

    <div class="doc-header">
        @if ($companyName)
            <p class="doc-company">{{ $companyName }}</p>
        @endif
        <h1 class="doc-title">{{ $definition->label() }}</h1>
        <p class="doc-meta">
            {{ __('data_transfer.generated_on', ['date' => $generatedAt]) }}
            @if ($generatedBy)
                &nbsp;·&nbsp; {{ __('data_transfer.generated_by', ['user' => $generatedBy]) }}
            @endif
            &nbsp;·&nbsp; {{ __('data_transfer.records') }}: {{ number_format($rows->count()) }}
        </p>
    </div>

    @if ($wasTruncated)
        <p class="notice">{{ __('data_transfer.pdf_truncated', ['count' => number_format($maxRows)]) }}</p>
    @endif

    @if (! empty($filters))
        <p class="filters">
            <strong>{{ __('data_transfer.filters') }}:</strong>
            @foreach ($filters as $key => $value)
                {{ \Illuminate\Support\Str::headline($key) }} = {{ is_array($value) ? implode(', ', $value) : $value }}@if (! $loop->last) &nbsp;·&nbsp; @endif
            @endforeach
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th class="col-num">#</th>
                @foreach ($columns as $column)
                    <th>{{ $column->getLabel() }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="col-num">{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        @php ($value = $column->exportValue($row))
                        <td>
                            @if ($value === null || $value === '')
                                <span class="empty">&mdash;</span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="empty" style="text-align: center; padding: 14px;">
                        {{ __('data_transfer.reference_none') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="doc-footer">
        <span>{{ config('app.name', 'RAMS') }}</span>
        <span class="right">{{ $generatedAt }}</span>
    </div>

</body>
</html>

# ADR-0008: UTC Storage for All Timestamps

## Status

Accepted

## Date

2024-01-01

## Context

RAMS is a SaaS platform intended to serve religious organizations across multiple countries and time zones:

- Pakistan (PKT — UTC+5)
- Malaysia (MYT — UTC+8)
- United Kingdom (GMT/BST — UTC+0/+1)
- United States (EST/PST — UTC-5/-8)
- Middle East (AST/GST — UTC+3/+4)

Each company may be in a different time zone.
Attendance records, audit logs, activity logs, and scheduled reports all contain timestamps.

The core problem: if timestamps are stored in local time, they become ambiguous:

- `2024-03-31 02:30:00` in Pakistan does not sort correctly with `2024-03-31 02:30:00` in the UK (which was in DST transition)
- Comparing timestamps across tenants in different time zones produces incorrect results
- Exporting attendance across a daylight saving time boundary creates duplicate or missing hours

Alternatives evaluated:

- **Store in local time** — Simple for single-region apps. Breaks comparisons, sorting, and aggregation across time zones. Reports crossing DST boundaries are incorrect.
- **Store in UTC, display in local time** — Standard industry practice. UTC is unambiguous. Display layer handles conversion per company/user timezone.
- **Store Unix timestamps (integers)** — UTC by definition but less readable in DB, harder to query by date range directly.
- **Store both UTC and local** — Redundant storage, synchronization risk.

## Decision

All timestamps stored in the database are in **UTC**.

Rules:

1. `config/app.php` — `timezone` is set to `UTC`
2. MySQL columns — all `TIMESTAMP` and `DATETIME` columns store UTC
3. Eloquent casts — `created_at`, `updated_at`, and all date fields use `datetime` cast (Carbon in UTC)
4. Company settings — each Company record has a `timezone` field (e.g., `Asia/Karachi`, `Asia/Kuala_Lumpur`)
5. Display — all timestamps are converted to the company's local timezone at the presentation layer (Blade/API response)
6. User input — dates/times submitted by users are treated as company-local time and converted to UTC before storage

Helper pattern in Services:

```php
// Convert company local time to UTC before storing
$utcTime = Carbon::createFromFormat('Y-m-d H:i', $localInput, $company->timezone)
    ->setTimezone('UTC');
```

Helper pattern in Blade/API:

```php
// Convert UTC to company local time for display
$localTime = $record->created_at->setTimezone($company->timezone);
```

## Consequences

### Positive

- Timestamps sort and compare correctly across all tenants and time zones
- No data corruption during DST transitions (UTC has no DST)
- Audit logs and activity logs are globally consistent and comparable
- Reports that span midnight in local time calculate correctly in UTC
- Standard industry practice — every developer understands UTC storage
- MySQL `TIMESTAMP` type is UTC-aware natively; `DATETIME` stores exactly what is provided (always UTC)

### Negative

- Developers must remember to always convert timestamps at the display layer — raw DB values are UTC, not local
- A mistake at the display layer (forgetting the timezone conversion) shows the user incorrect times
- Date-range queries submitted by users (e.g., "show attendance for 1 April") must account for the company's timezone when building the UTC query range

### Neutral

- The company `timezone` field is mandatory — a Company without a timezone cannot display correct timestamps
- All API responses must include timezone-converted timestamps OR include the UTC timestamp and the timezone string so the client can convert
- The application timezone (`APP_TIMEZONE=UTC`) must never be changed to a local timezone
- Scheduled jobs (e.g., "send daily attendance report at 9am") must calculate the correct UTC time based on each company's timezone

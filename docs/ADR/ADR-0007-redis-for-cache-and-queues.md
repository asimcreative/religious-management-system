# ADR-0007: Redis for Cache and Queues

## Status

Accepted

## Date

2024-01-01

## Context

As RAMS grows in usage, two categories of operations become bottlenecks:

**Category 1: Expensive repeated reads**

- Dashboard statistics (attendance counts, employee counts, class progress summaries) are queried on every page load
- Report generation aggregates across large tables
- Permission lookups happen on every authenticated request (Spatie Permission uses its own cache)

**Category 2: Operations that should not block the HTTP response**

- Sending email or SMS notifications to employees
- Generating PDF reports for large datasets
- Bulk attendance imports
- Scheduled report generation (daily/weekly/monthly)

Using the database for caching is inefficient.
Using `sync` driver for queues means slow HTTP responses whenever a notification is sent.
`file` driver for both cache and queues works but does not scale beyond a single server.

Alternatives evaluated:

- **File Cache / File Queue** — No additional infrastructure. Works but does not scale horizontally. File locking issues under concurrent load. Not suitable for production SaaS.
- **Database Cache / Database Queue** — Uses MySQL for cache and job storage. Slower than Redis. Adds load to the main DB. Queue polling creates frequent DB writes.
- **Memcached** — Good for caching only. No queue support. Less feature-rich than Redis (no pub/sub, no sorted sets, no persistence).
- **Redis** — In-memory key-value store. Supports both cache (with TTL) and queue (list-based). Native Laravel driver for both cache and queue. Also supports Horizon for real-time queue monitoring.

## Decision

We use **Redis** for both caching and queue processing.

Configuration:

- `CACHE_DRIVER=redis` — All `Cache::put()` / `Cache::remember()` calls use Redis
- `QUEUE_CONNECTION=redis` — All `dispatch()` calls push jobs to Redis queues
- **Laravel Horizon** — Deployed alongside the application to provide a real-time queue dashboard, job metrics, and retry UI

Queue separation:

| Queue Name | Purpose |
|------------|---------|
| `default` | Standard operations |
| `notifications` | Email/SMS dispatch |
| `reports` | Report generation, PDF export |
| `imports` | Bulk attendance/employee imports |

Cache strategy:

| Data | TTL | Key Pattern |
|------|-----|-------------|
| Dashboard stats | 5 minutes | `company:{id}:dashboard:stats` |
| Permission cache | Until reset | Managed by Spatie |
| Report summaries | 1 hour | `company:{id}:report:{type}:{date}` |

## Consequences

### Positive

- Sub-millisecond cache reads vs. milliseconds for DB queries
- Non-blocking HTTP responses — jobs dispatched instantly, processed asynchronously
- Laravel Horizon provides a professional queue monitoring UI
- Queue retries, failed job tracking, and job metrics are built in
- Horizontal scaling: multiple workers can process from the same Redis instance
- Spatie Permission cache benefits automatically from Redis (faster permission lookups)
- Separate queue channels allow prioritizing notifications over long-running reports

### Negative

- Redis is an additional infrastructure component to manage, monitor, and back up
- Redis data is in-memory by default — a Redis restart without persistence configured loses queued jobs
- Redis requires configuring persistence (`AOF` or `RDB`) in production to survive restarts
- Laravel Horizon requires a separate supervisor process to run continuously

### Neutral

- In development environments, `CACHE_DRIVER=array` and `QUEUE_CONNECTION=sync` can be used to simplify local setup
- All cache keys must include `company_id` to prevent cross-tenant cache pollution
- Cache invalidation must be triggered from the Service layer whenever data changes (not from Repositories or Controllers)
- Failed jobs must be monitored via Horizon; unresolved failures alert the operations team

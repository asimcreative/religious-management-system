# RAMS — Performance Checklist

> Version: v1.0.0 | Date: 2026-08-04

This document covers performance optimisations implemented in RAMS and the review checklist for future development.

---

## Implemented Optimisations (v1.0.0)

### Database

| Optimisation | Location | Status |
|---|---|---|
| Index on `company_id` (all business tables) | All migrations | ✅ Implemented |
| Composite index on `(company_id, created_at)` | Activity log, attendance, notification tables | ✅ Implemented |
| Index on `employee_id`, `teacher_id` foreign keys | Attendance and relation tables | ✅ Implemented |
| Index on `quran_class_id` | `quran_class_members`, `quran_attendances` | ✅ Implemented |
| Index on `jamaat_id` | `jamaat_members`, `salah_attendances` | ✅ Implemented |
| Index on `(model_type, model_id)` | `activity_log` | ✅ Implemented |
| Soft delete index on `deleted_at` | All SoftDeletes models | ✅ Indexed |
| PDO persistent connections | MySQL via Laravel config | ✅ Default |
| Eager loading used in repositories | `with()` on all list queries | ✅ Implemented |
| Pagination on all list views | `paginate(20)` default | ✅ Implemented |
| Chunk processing in purge command | 1000 rows per chunk | ✅ Implemented |

### Caching

| Cache | Driver | TTL | Status |
|---|---|---|---|
| Dashboard statistics | Redis | 5 minutes | ✅ Implemented |
| Permission roles | Redis (Spatie cache) | Until role change | ✅ Framework default |
| Laravel config cache | File (artisan command) | Until cleared | ✅ Documented |
| Laravel route cache | File (artisan command) | Until cleared | ✅ Documented |
| Laravel view cache | File (artisan command) | Until cleared | ✅ Documented |
| Laravel event cache | File (artisan command) | Until cleared | ✅ Documented |

### Queue & Background Processing

| Job Type | Queue | Priority | Status |
|---|---|---|---|
| Notifications | `high` | Immediate | ✅ Configured |
| Emails | `high` | Immediate | ✅ Configured |
| Standard operations | `default` | Normal | ✅ Configured |
| Excel/PDF exports | `low` | Background | ✅ Configured |
| Report generation | `low` | Background | ✅ Configured |
| Log purge | Scheduler (daily) | Off-peak | ✅ Scheduled |

### Frontend

| Optimisation | Implementation | Status |
|---|---|---|
| Vite asset bundling | `npm run build` — production bundle | ✅ Implemented |
| CSS/JS minification | Vite default in production mode | ✅ Implemented |
| Asset versioning | Vite content hash in filenames | ✅ Implemented |
| Bootstrap 5 (CDN-free) | Bundled via npm, not CDN | ✅ Implemented |
| jQuery bundled | Via npm/Vite | ✅ Implemented |
| DataTables for large lists | If enabled in views | ✅ Available |

### HTTP & Server

| Optimisation | Implementation | Status |
|---|---|---|
| Gzip compression | Nginx `gzip on;` | ✅ Required (see Nginx config) |
| Static asset caching | `expires 1y` for `/build/*` | ✅ Required (see Nginx config) |
| PHP-FPM process pool | Tuned to server CPU count | ✅ Documented |
| OPcache enabled | PHP OPcache for bytecode caching | ✅ Required |
| Redis for session | `SESSION_DRIVER=redis` (optional upgrade) | ⚠️ Currently DB |

---

## Performance Review Checklist (Per Feature)

### Database Queries

- [ ] New queries include `where('company_id', ...)` or use a scoped model (avoids full-table scans)
- [ ] N+1 queries eliminated — use `with()` for relationships loaded in loops
- [ ] New tables have index on `company_id`
- [ ] Composite index added if queries filter on 2+ columns together
- [ ] Foreign key columns are indexed
- [ ] Aggregation queries (COUNT, SUM) are cached when used in dashboards
- [ ] Large exports go through queue (not synchronous HTTP request)
- [ ] `EXPLAIN` run on any new query affecting tables over 10,000 rows
- [ ] Soft-delete queries include `whereNull('deleted_at')` index coverage

### Eloquent / Repository

- [ ] No lazy-loading in list views (check for `Strict Mode` or Telescope N+1 detection)
- [ ] `select()` used to limit columns when full model not needed
- [ ] `chunk()` or cursor used when processing large datasets in commands
- [ ] `pluck()` instead of `get()` when only one column needed
- [ ] `count()` without loading models when only count is needed
- [ ] Pagination (`paginate()`, `simplePaginate()`) used on all list endpoints

### Caching

- [ ] Dashboard stats are cached (avoid recalculating on every page load)
- [ ] Cache keys include `company_id` to prevent cross-tenant cache poisoning
- [ ] Cache is invalidated on write (avoid stale data)
- [ ] TTL set appropriately (not too long for mutable data)
- [ ] Redis used for cache in production (not file driver)

### Queue

- [ ] Heavy operations (exports, emails, reports) dispatched to queue
- [ ] Correct queue priority used: `high` for notifications, `low` for exports
- [ ] Queue jobs are idempotent (safe to retry on failure)
- [ ] Job timeout set appropriately (long-running jobs have extended timeout)
- [ ] Jobs implement `ShouldQueue` interface

### Frontend

- [ ] No unbundled CSS/JS files (all go through Vite)
- [ ] Images are optimised before commit
- [ ] No inline JavaScript for logic that belongs in separate files
- [ ] Blade templates do not execute queries (data should come from controller)
- [ ] Large tables paginated (not all-at-once rendering)

---

## Performance Targets (v1.0.0 Baseline)

| Metric | Target | Environment |
|---|---|---|
| Page load — Dashboard | < 500 ms | Production with cache warm |
| Page load — Employee list (20 rows) | < 300 ms | Production |
| API response — GET /employees | < 200 ms | Production |
| API response — POST /login | < 400 ms | Production |
| Report generation — PDF | < 5 s | Production (via queue) |
| Report generation — Excel | < 10 s | Production (via queue) |
| Queue job — notification dispatch | < 30 s | Production |
| Database query — attendance list | < 100 ms | Production |

---

## Redis Configuration (Production)

```ini
# /etc/redis/redis.conf (recommended settings for RAMS)
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
appendonly yes
```

---

## PHP OPcache Configuration (Production)

```ini
; /etc/php/8.3/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

> Note: Set `opcache.validate_timestamps=0` only in production. Requires OPcache reset after each deployment (`php artisan opcache:clear` or PHP-FPM reload).

---

## PHP-FPM Pool Configuration (Production)

```ini
; /etc/php/8.3/fpm/pool.d/www.conf (recommended)
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
request_terminate_timeout = 120
```

> Adjust `pm.max_children` based on available RAM: each PHP-FPM worker uses approximately 30–50 MB.

---

## Horizon Queue Tuning (Production)

From `config/horizon.php` — production supervisor settings:

| Supervisor | Workers | Queue | Timeout |
|---|---|---|---|
| supervisor-high | 4 max | `high` | 30 s |
| supervisor-default | 6 max | `default` | 60 s |
| supervisor-low | 3 max | `low` | 300 s |

Auto-scaling strategy: `time` — Horizon scales workers based on queue wait time vs thresholds (`high: 30s`, `default: 60s`, `low: 120s`).

---

## Monitoring Queries (Run in Production to Verify)

```sql
-- Check for missing company_id indexes
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'rams_production'
  AND COLUMN_NAME = 'company_id'
ORDER BY TABLE_NAME;

-- Check for slow queries (requires slow query log enabled)
SHOW VARIABLES LIKE 'slow_query_log%';

-- Check table sizes
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'rams_production'
ORDER BY size_mb DESC;

-- Check active queue jobs
SELECT queue, COUNT(*) as pending FROM jobs GROUP BY queue;

-- Check failed jobs
SELECT COUNT(*) FROM failed_jobs WHERE failed_at > NOW() - INTERVAL 24 HOUR;
```

---

*Performance checklist maintained by: Lead Developer*
*Last reviewed: 2026-08-04 | RAMS v1.0.0*

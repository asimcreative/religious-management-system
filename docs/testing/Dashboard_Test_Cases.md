# Dashboard — Test Cases

**Module:** Web Dashboard + API Dashboard
**Permissions:** `report.dashboard`
**Routes:** `GET /dashboard`, `GET /api/v1/dashboard`

---

## Web Dashboard

| ID | Title | Priority | Preconditions | Steps | Expected Result |
|----|-------|----------|---------------|-------|-----------------|
| DASH-001 | Dashboard redirects unauthenticated user | Critical | Not logged in | GET `/dashboard` | Redirect to `/login` |
| DASH-002 | Dashboard requires `report.dashboard` permission | Critical | Logged in, no permission | GET `/dashboard` | HTTP 403 |
| DASH-003 | Dashboard returns 200 with permission | High | User has `report.dashboard` | GET `/dashboard` | HTTP 200 |
| DASH-004 | Dashboard shows only own company counts | Critical | Company A: 5 employees, Company B: 10 | View dashboard as Company A | Shows 5 employees, not 15 |
| DASH-005 | Super Admin sees global aggregated data | High | Super Admin role, 2 companies | View dashboard | Shows data across all companies |
| DASH-006 | Tenant Super Admin sees only own company | High | Tenant Super Admin (not SYSTEM) | View dashboard | Only own company data |
| DASH-007 | Employee count widget is accurate | High | 5 active employees, 1 soft-deleted | View dashboard | Shows 5 (excludes deleted) |
| DASH-008 | Teacher count widget is accurate | Medium | 2 teachers | View dashboard | Shows 2 |
| DASH-009 | Quran class count is accurate | Medium | 3 active classes | View dashboard | Shows 3 |
| DASH-010 | Inactive company user blocked before dashboard | Critical | Company is inactive | User tries to access dashboard | Redirect to login / HTTP 403 |
| DASH-011 | Inactive user blocked before dashboard | Critical | User `status=inactive` | User tries to access dashboard | Redirect to login |

---

## Dashboard Cache

| ID | Title | Priority | Preconditions | Steps | Expected Result |
|----|-------|----------|---------------|-------|-----------------|
| DASH-012 | Cache is invalidated when employee is created | High | Dashboard cache populated | Create new employee | Cache cleared for company |
| DASH-013 | Cache is invalidated when employee is deleted | High | Dashboard cache populated | Soft-delete employee | Cache cleared for company |
| DASH-014 | Cache is isolated per company | High | Two companies | Company A's cache cleared | Company B's cache untouched |
| DASH-015 | DashboardCacheObserver fires on model change | Medium | Observer registered | Create/update/delete any company model | Observer event fired, cache key removed |

---

## API Dashboard

| ID | Title | Priority | Preconditions | Steps | Expected Result |
|----|-------|----------|---------------|-------|-----------------|
| DASH-016 | API dashboard requires Bearer token | Critical | No auth header | GET `/api/v1/dashboard` | HTTP 401 with JSON error envelope |
| DASH-017 | API dashboard requires `report.dashboard` | Critical | Valid token, no permission | GET `/api/v1/dashboard` | HTTP 403 with JSON error envelope |
| DASH-018 | API dashboard returns JSON with counts | High | Valid token with permission | GET `/api/v1/dashboard` | HTTP 200, JSON with `employees`, `teachers`, etc. |
| DASH-019 | API dashboard data scoped to company | Critical | Two companies | Request as Company A | Only Company A counts returned |
| DASH-020 | API dashboard with date filter | Medium | Has permission | GET `/api/v1/dashboard?from=2026-01-01&to=2026-12-31` | Filtered counts returned |

---

## Date Filters

| ID | Title | Priority | Preconditions | Steps | Expected Result |
|----|-------|----------|---------------|-------|-----------------|
| DASH-021 | Dashboard uses company timezone for date comparison | High | Company timezone set to `Asia/Karachi` | View dashboard at midnight UTC | Correct date used for company |
| DASH-022 | Invalid date range handled gracefully | Low | Has permission | GET with `from=invalid-date` | Validation error or fallback to default range |

---

*Total: 22 test cases*

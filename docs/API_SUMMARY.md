# RAMS REST API Summary

Base URL: `/api/v1`
Authentication: Bearer token (Laravel Sanctum)
Token prefix: `rams_`
Token expiry: 30 days (configurable via `SANCTUM_TOKEN_EXPIRATION`)

---

## Authentication

### POST `/api/v1/login`
Rate limited: 5 requests per minute.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "mobile-app"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|rams_abc...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Ahmed Khan",
      "email": "ahmed@org.com",
      "company_id": 1,
      "language": "en",
      "roles": ["Admin"]
    }
  }
}
```

**Error responses:**
- `422` — Invalid credentials
- `403` — Account or company inactive

---

### POST `/api/v1/logout`
Revokes the current access token.

---

### GET `/api/v1/profile`
Returns the authenticated user's profile, roles, and permissions.

---

### PUT `/api/v1/profile`
Update name, mobile, language (`en` or `ur`).

---

### PUT `/api/v1/change-password`
```json
{
  "current_password": "old",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```
Revokes all other tokens on success.

---

### GET `/api/v1/me/unread-notifications-count`
Returns `{ "count": 3 }`.

---

## Dashboard

### GET `/api/v1/dashboard`
Returns all KPI stats, today's attendance, and module summaries. Data is cached per company (5–10 min TTL).

---

## Employees

### GET `/api/v1/employees`
Query parameters: `search`, `status`, `branch_id`, `department_id`, `per_page` (default 15).

### GET `/api/v1/employees/{id}`

---

## Teachers

### GET `/api/v1/teachers`
Query parameters: `search`, `status`, `per_page`.

### GET `/api/v1/teachers/{id}`
Includes assigned branches (loaded via `whenLoaded`).

---

## Quran Module

### GET `/api/v1/quran/classes`
Query parameters: `search`, `status`, `per_page`.

### GET `/api/v1/quran/classes/{id}`

### GET `/api/v1/quran/attendance`
Query parameters: `date`, `class_id`, `per_page`.

---

## Salah Module

### GET `/api/v1/salah/jamaats`
Query parameters: `search`, `status`, `per_page`.

### GET `/api/v1/salah/jamaats/{id}`

### GET `/api/v1/salah/attendance`
Query parameters: `date`, `jamaat_id`, `prayer_id`, `per_page`.

---

## Notifications

### GET `/api/v1/notifications`
Query parameters: `unread_only` (boolean), `per_page`.

### POST `/api/v1/notifications/{id}/read`
Marks a single notification as read.

### POST `/api/v1/notifications/read-all`
Marks all notifications as read.

### DELETE `/api/v1/notifications/{id}`
Deletes a notification.

---

## Standard Response Envelope

All responses use a consistent JSON envelope:

```json
{
  "success": true,
  "message": "Optional message",
  "data": { ... }
}
```

Paginated responses include a `meta` key with `current_page`, `last_page`, `per_page`, `total`.

Error responses:
```json
{
  "success": false,
  "message": "Error description"
}
```

---

## Rate Limits

| Endpoint group | Limit |
|---|---|
| Login | 5 requests / minute |
| All authenticated endpoints | 60 requests / minute |

---

## Company Isolation

The API is fully multi-tenant. All data returned is automatically scoped to the authenticated user's `company_id`. No cross-company data leakage is possible.

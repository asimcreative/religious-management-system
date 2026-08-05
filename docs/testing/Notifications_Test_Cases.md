# Notifications Module -- Test Cases

**Project:** Religious Affairs Management System (RAMS)
**Module:** Notifications
**Version:** 1.0.0
**Date:** 2026-08-05
**Author:** QA Team / RAMS Architect
**Format:** Manual + Automated (PHPUnit Feature Tests)

---

## Legend

| Symbol | Meaning |
|--------|---------|
| Critical | Security breach, data leak, IDOR, ownership bypass |
| High | Major feature broken, wrong data, wrong HTTP status |
| Medium | Wrong count, minor mismatch, UX issue |
| Low | Cosmetic, label |

---

## Scope

**Web Routes** (middleware: auth, company.active, user.active):

| Route | Method | Permission |
|-------|--------|------------|
| GET /notifications | index | notification.view |
| POST /notifications/{id}/mark-read | markRead | notification.read |
| POST /notifications/mark-all-read | markAllRead | notification.read |
| DELETE /notifications/{id} | destroy | notification.delete |
| GET /notifications/unread-count | unreadCount | notification.view |

**API Routes** (middleware: auth:sanctum, api.account.active, permission.team, throttle:60,1):

| Route | Method | Permission |
|-------|--------|------------|
| GET /api/v1/notifications | index | notification.view |
| POST /api/v1/notifications/{id}/read | markRead | notification.read |
| POST /api/v1/notifications/read-all | markAllRead | notification.read |
| DELETE /api/v1/notifications/{id} | destroy | notification.delete |

---

## Preconditions (Global)

- Laravel 12 with MySQL 8 and a seeded database.
- Notification records have: company_id (from user.company_id), user_id, title, message, type, priority, read_at (nullable).
- NotificationService enforces ownership via WHERE user_id = Auth::id() on all queries.
- Company A User 1 and Company A User 2 are separate accounts for cross-user isolation tests.
- Company B User 1 is a separate account for cross-company isolation tests.
- Sanctum API tokens created for API test cases.
- NotificationService.notify() used to seed notification records in preconditions.

---

## Section 1 -- Authentication Guard (Web)

| ID | TC-NOT-001 |
|---|---|
| **Title** | Unauthenticated user redirected from notification index |
| **Priority** | Critical |
| **Preconditions** | No active session. |
| **Steps** | 1. GET /notifications without session. |
| **Expected Result** | HTTP 302 redirect to /login. No notification data returned. |

| ID | TC-NOT-002 |
|---|---|
| **Title** | Unauthenticated user cannot mark notification as read |
| **Priority** | Critical |
| **Preconditions** | No active session. |
| **Steps** | 1. POST /notifications/1/mark-read without session. |
| **Expected Result** | HTTP 302 to /login. |

| ID | TC-NOT-003 |
|---|---|
| **Title** | Unauthenticated user cannot delete notification |
| **Priority** | Critical |
| **Preconditions** | No active session. |
| **Steps** | 1. DELETE /notifications/1 without session. |
| **Expected Result** | HTTP 302 to /login. |

## Section 2 -- Permission Enforcement (Web)

| ID | TC-NOT-010 |
|---|---|
| **Title** | User without notification.view gets 403 on notification index |
| **Priority** | High |
| **Preconditions** | Authenticated user with no notification.* permissions. |
| **Steps** | 1. Authenticate user. 2. GET /notifications. |
| **Expected Result** | HTTP 403. authorize(notification.view) blocks. |

| ID | TC-NOT-011 |
|---|---|
| **Title** | User with notification.view can access notification index |
| **Priority** | High |
| **Preconditions** | Authenticated user with notification.view. |
| **Steps** | 1. GET /notifications. |
| **Expected Result** | HTTP 200. Notification list rendered with pagination and unread count. |

| ID | TC-NOT-012 |
|---|---|
| **Title** | User without notification.read gets 403 on mark-read |
| **Priority** | High |
| **Preconditions** | Authenticated user with notification.view but NOT notification.read. |
| **Steps** | 1. POST /notifications/{id}/mark-read. |
| **Expected Result** | HTTP 403. authorize(notification.read) blocks. |

| ID | TC-NOT-013 |
|---|---|
| **Title** | User without notification.delete gets 403 on delete |
| **Priority** | High |
| **Preconditions** | Authenticated user without notification.delete. |
| **Steps** | 1. DELETE /notifications/{id}. |
| **Expected Result** | HTTP 403. authorize(notification.delete) blocks. |

| ID | TC-NOT-014 |
|---|---|
| **Title** | User with notification.read can mark notification as read |
| **Priority** | High |
| **Preconditions** | Authenticated user with notification.view and notification.read. Own notification exists. |
| **Steps** | 1. POST /notifications/{own_id}/mark-read. |
| **Expected Result** | HTTP 200 (JSON) or redirect back. Notification read_at is now set. |

| ID | TC-NOT-015 |
|---|---|
| **Title** | User with notification.delete can delete own notification |
| **Priority** | High |
| **Preconditions** | Authenticated user with notification.delete. Own notification exists. |
| **Steps** | 1. DELETE /notifications/{own_id}. |
| **Expected Result** | Notification removed from DB. Redirect back with success message. |

| ID | TC-NOT-016 |
|---|---|
| **Title** | User with notification.view can get unread count |
| **Priority** | High |
| **Preconditions** | Authenticated user with notification.view. 3 unread notifications exist. |
| **Steps** | 1. GET /notifications/unread-count. |
| **Expected Result** | HTTP 200 JSON. Response contains count key with integer value 3. |

## Section 3 -- mark-read Behaviour

| ID | TC-NOT-020 |
|---|---|
| **Title** | mark-read sets read_at timestamp on the notification |
| **Priority** | High |
| **Preconditions** | User has notification.read. Own unread notification exists (read_at=NULL). |
| **Steps** | 1. POST /notifications/{id}/mark-read. |
| **Expected Result** | DB record now has read_at set to current datetime (not NULL). Subsequent GET /notifications shows notification as read. |

| ID | TC-NOT-021 |
|---|---|
| **Title** | mark-read on already-read notification does not throw error |
| **Priority** | Medium |
| **Preconditions** | User has notification.read. Own notification already has read_at set. |
| **Steps** | 1. POST /notifications/{id}/mark-read again. |
| **Expected Result** | HTTP 200 or redirect. No exception. read_at unchanged (still the original timestamp). |

| ID | TC-NOT-022 |
|---|---|
| **Title** | mark-read returns 404 when notification does not belong to the user |
| **Priority** | Critical |
| **Preconditions** | User A and User B both exist. Notification belongs to User B. |
| **Steps** | 1. Authenticate as User A. 2. POST /notifications/{UserB_notification_id}/mark-read. |
| **Expected Result** | HTTP 404. abort_unless fails because markAsRead() returns false (WHERE user_id=UserA.id finds no match). User B notification read_at unchanged. |

| ID | TC-NOT-023 |
|---|---|
| **Title** | mark-all-read sets read_at on all own unread notifications |
| **Priority** | High |
| **Preconditions** | User has notification.read. User A has 5 unread notifications. User B has 3 unread notifications. |
| **Steps** | 1. Authenticate as User A. 2. POST /notifications/mark-all-read. |
| **Expected Result** | HTTP 200 JSON with marked=5 (or redirect). All 5 User A notifications now have read_at set. User B 3 notifications remain unread -- markAllAsRead() scopes to WHERE user_id=UserA.id only. |

| ID | TC-NOT-024 |
|---|---|
| **Title** | mark-all-read only affects own notifications, never other users |
| **Priority** | Critical |
| **Preconditions** | User A: 5 unread. User B: 3 unread. User A has notification.read. |
| **Steps** | 1. Authenticate as User A. 2. POST /notifications/mark-all-read. |
| **Expected Result** | User A: all 5 now read. User B: all 3 still unread. Cross-user contamination impossible because markAllAsRead() uses WHERE user_id=UserId. |

| ID | TC-NOT-025 |
|---|---|
| **Title** | mark-all-read returns zero when user has no unread notifications |
| **Priority** | Medium |
| **Preconditions** | User A has notification.read. All notifications already read. |
| **Steps** | 1. POST /notifications/mark-all-read. |
| **Expected Result** | HTTP 200. JSON response: marked=0. No error. |

## Section 4 -- Delete Behaviour

| ID | TC-NOT-030 |
|---|---|
| **Title** | User can delete own notification |
| **Priority** | High |
| **Preconditions** | User has notification.delete. Own notification exists. |
| **Steps** | 1. DELETE /notifications/{own_id}. |
| **Expected Result** | DB record deleted. Redirect back with success message. Notification no longer appears in index. |

| ID | TC-NOT-031 |
|---|---|
| **Title** | User cannot delete another user notification -- returns 404 |
| **Priority** | Critical |
| **Preconditions** | User A has notification.delete. Notification belongs to User B. |
| **Steps** | 1. Authenticate as User A. 2. DELETE /notifications/{UserB_notification_id}. |
| **Expected Result** | HTTP 404. abort_unless fires because delete() returns false (WHERE user_id=UserA.id matches nothing). User B notification intact in DB. |

| ID | TC-NOT-032 |
|---|---|
| **Title** | User cannot delete notification from another company |
| **Priority** | Critical |
| **Preconditions** | User from Company A has notification.delete. Notification belongs to Company B user. |
| **Steps** | 1. Authenticate as Company A user. 2. DELETE /notifications/{CompanyB_notification_id}. |
| **Expected Result** | HTTP 404. Notification scoped by user_id prevents cross-company access. Company B notification untouched. |

| ID | TC-NOT-033 |
|---|---|
| **Title** | Deleting a non-existent notification returns 404 |
| **Priority** | Medium |
| **Preconditions** | User has notification.delete. Notification ID does not exist. |
| **Steps** | 1. DELETE /notifications/99999. |
| **Expected Result** | HTTP 404. abort_unless fires. No exception. |

## Section 5 -- unread-count Endpoint

| ID | TC-NOT-040 |
|---|---|
| **Title** | unread-count returns correct integer for authenticated user |
| **Priority** | High |
| **Preconditions** | User has notification.view. 4 unread notifications belong to user. 2 read notifications also exist. |
| **Steps** | 1. GET /notifications/unread-count. |
| **Expected Result** | HTTP 200 JSON: {count: 4}. Only unread (read_at=NULL) counted. Read notifications not included. |

| ID | TC-NOT-041 |
|---|---|
| **Title** | unread-count returns zero when user has no unread notifications |
| **Priority** | Medium |
| **Preconditions** | User has notification.view. All notifications are read. |
| **Steps** | 1. GET /notifications/unread-count. |
| **Expected Result** | HTTP 200 JSON: {count: 0}. No error. |

| ID | TC-NOT-042 |
|---|---|
| **Title** | unread-count scoped to authenticated user only |
| **Priority** | Critical |
| **Preconditions** | User A has 4 unread. User B has 7 unread. Both in Company A. |
| **Steps** | 1. Authenticate as User A. 2. GET /notifications/unread-count. |
| **Expected Result** | JSON: {count: 4}. User B count not included. getUnreadCount(Auth::id()) scoped to User A only. |

| ID | TC-NOT-043 |
|---|---|
| **Title** | unread-count requires notification.view permission |
| **Priority** | High |
| **Preconditions** | Authenticated user without notification.view. |
| **Steps** | 1. GET /notifications/unread-count. |
| **Expected Result** | HTTP 403. authorize(notification.view) blocks. |

## Section 6 -- API Notifications

| ID | TC-NOT-050 |
|---|---|
| **Title** | API: unauthenticated request returns 401 |
| **Priority** | Critical |
| **Preconditions** | No Sanctum token provided. |
| **Steps** | 1. GET /api/v1/notifications with no Authorization header. |
| **Expected Result** | HTTP 401 JSON: {message: Unauthenticated}. No notification data returned. |

| ID | TC-NOT-051 |
|---|---|
| **Title** | API: valid token returns user notifications as JSON |
| **Priority** | High |
| **Preconditions** | User has valid Sanctum token. Has notification.view. 3 notifications exist for user. |
| **Steps** | 1. GET /api/v1/notifications with Bearer token. |
| **Expected Result** | HTTP 200 JSON. Paginated notification list containing 3 items. NotificationResource format applied. |

| ID | TC-NOT-052 |
|---|---|
| **Title** | API: notifications scoped to authenticated user only |
| **Priority** | Critical |
| **Preconditions** | User A has 3 notifications. User B has 5. User A has valid token. |
| **Steps** | 1. GET /api/v1/notifications as User A. |
| **Expected Result** | Response contains exactly 3 items (User A only). User B notifications absent. getForUser(user.id) enforces user scoping. |

| ID | TC-NOT-053 |
|---|---|
| **Title** | API: notifications scoped to authenticated user company only |
| **Priority** | Critical |
| **Preconditions** | Company A user has token. Company B user has notifications. |
| **Steps** | 1. GET /api/v1/notifications as Company A user. |
| **Expected Result** | Only Company A user notifications returned. Scoped by user_id which is inherently company-isolated. |

| ID | TC-NOT-054 |
|---|---|
| **Title** | API: mark notification read via POST |
| **Priority** | High |
| **Preconditions** | User has valid token and notification.read. Own unread notification exists. |
| **Steps** | 1. POST /api/v1/notifications/{id}/read. |
| **Expected Result** | HTTP 200 JSON: {success: true, message: Notification marked as read}. DB read_at set. |

| ID | TC-NOT-055 |
|---|---|
| **Title** | API: mark read on another user notification returns 404 JSON |
| **Priority** | Critical |
| **Preconditions** | User A has token. Notification belongs to User B. |
| **Steps** | 1. POST /api/v1/notifications/{UserB_notification_id}/read as User A. |
| **Expected Result** | HTTP 404 JSON: {message: Notification not found}. User B notification read_at unchanged. |

| ID | TC-NOT-056 |
|---|---|
| **Title** | API: mark-all-read only marks own notifications |
| **Priority** | Critical |
| **Preconditions** | User A: 4 unread. User B: 3 unread. User A has token and notification.read. |
| **Steps** | 1. POST /api/v1/notifications/read-all as User A. |
| **Expected Result** | HTTP 200 JSON: {marked: 4}. User B notifications remain unread. |

| ID | TC-NOT-057 |
|---|---|
| **Title** | API: delete own notification returns 204 |
| **Priority** | High |
| **Preconditions** | User has token and notification.delete. Own notification exists. |
| **Steps** | 1. DELETE /api/v1/notifications/{own_id}. |
| **Expected Result** | HTTP 204 No Content. Notification deleted from DB. |

| ID | TC-NOT-058 |
|---|---|
| **Title** | API: delete another user notification returns 404 |
| **Priority** | Critical |
| **Preconditions** | User A has token and notification.delete. Notification belongs to User B. |
| **Steps** | 1. DELETE /api/v1/notifications/{UserB_id} as User A. |
| **Expected Result** | HTTP 404 JSON: {message: Notification not found}. User B notification intact. |

| ID | TC-NOT-059 |
|---|---|
| **Title** | API: expired or invalid token returns 401 |
| **Priority** | Critical |
| **Preconditions** | Invalid or expired Sanctum token. |
| **Steps** | 1. GET /api/v1/notifications with Authorization: Bearer invalid_token. |
| **Expected Result** | HTTP 401 JSON error. No data returned. |

## Section 7 -- NotificationService Unit Behaviour

| ID | TC-NOT-060 |
|---|---|
| **Title** | NotificationService.notify() creates correct DB record |
| **Priority** | High |
| **Preconditions** | User A exists with company_id=1. title and message provided. |
| **Steps** | 1. Call NotificationService.notify(UserA, title=Test Alert, message=Body text, type=security, priority=high). |
| **Expected Result** | DB record created in notifications table with: company_id=1, user_id=UserA.id, title=Test Alert, message=Body text, type=security, priority=high, read_at=NULL. |

| ID | TC-NOT-061 |
|---|---|
| **Title** | NotificationService.notify() sets company_id from user.company_id |
| **Priority** | Critical |
| **Preconditions** | User belongs to Company A (company_id=10). |
| **Steps** | 1. Call notify() with no explicit company_id argument. |
| **Expected Result** | Record created with company_id=10 (pulled from user.company_id). Company isolation preserved. |

| ID | TC-NOT-062 |
|---|---|
| **Title** | NotificationService.getUnreadCount() returns correct integer |
| **Priority** | High |
| **Preconditions** | User A has 5 unread and 2 read notifications. |
| **Steps** | 1. Call NotificationService.getUnreadCount(UserA.id). |
| **Expected Result** | Returns integer 5. Read notifications (read_at != NULL) not counted. |

| ID | TC-NOT-063 |
|---|---|
| **Title** | NotificationService.getUnreadCount() returns 0 for user with no notifications |
| **Priority** | Medium |
| **Preconditions** | User A has no notification records. |
| **Steps** | 1. Call NotificationService.getUnreadCount(UserA.id). |
| **Expected Result** | Returns integer 0. No exception. |

| ID | TC-NOT-064 |
|---|---|
| **Title** | NotificationService.markAsRead() sets read_at and returns true |
| **Priority** | High |
| **Preconditions** | Unread notification belongs to User A. |
| **Steps** | 1. Call NotificationService.markAsRead(notification_id, UserA.id). |
| **Expected Result** | Returns true. DB record read_at is set to a datetime value. |

| ID | TC-NOT-065 |
|---|---|
| **Title** | NotificationService.markAsRead() returns false for wrong user_id |
| **Priority** | High |
| **Preconditions** | Notification belongs to User B. |
| **Steps** | 1. Call NotificationService.markAsRead(notification_id, UserA.id). |
| **Expected Result** | Returns false. DB record read_at remains NULL. No exception. |

| ID | TC-NOT-066 |
|---|---|
| **Title** | NotificationService.markAllAsRead() returns count of notifications updated |
| **Priority** | High |
| **Preconditions** | User A has 5 unread notifications. |
| **Steps** | 1. Call NotificationService.markAllAsRead(UserA.id). |
| **Expected Result** | Returns integer 5. All 5 records now have read_at set. |

| ID | TC-NOT-067 |
|---|---|
| **Title** | NotificationService.delete() returns true for own notification |
| **Priority** | High |
| **Preconditions** | Notification belongs to User A. |
| **Steps** | 1. Call NotificationService.delete(notification_id, UserA.id). |
| **Expected Result** | Returns true. DB record deleted. |

| ID | TC-NOT-068 |
|---|---|
| **Title** | NotificationService.delete() returns false for another user notification |
| **Priority** | High |
| **Preconditions** | Notification belongs to User B. |
| **Steps** | 1. Call NotificationService.delete(notification_id, UserA.id). |
| **Expected Result** | Returns false. DB record not deleted. No exception. |

| ID | TC-NOT-069 |
|---|---|
| **Title** | NotificationService.notifyCompany() creates records for all active users |
| **Priority** | High |
| **Preconditions** | Company A has 3 active users and 1 inactive user. |
| **Steps** | 1. Call notifyCompany(companyId=1, title=Broadcast, message=Info). |
| **Expected Result** | 3 notifications created (one per active user). Inactive user gets no notification. Returns 3. |

## Section 8 -- Company Isolation

| ID | TC-NOT-070 |
|---|---|
| **Title** | Notification index shows only own user notifications |
| **Priority** | Critical |
| **Preconditions** | User A and User B both in Company A. User A has notification.view. 3 notifications for User A, 5 for User B. |
| **Steps** | 1. Authenticate as User A. 2. GET /notifications. |
| **Expected Result** | Page shows 3 notifications only (User A). User B notifications absent. getForUser(Auth::id()) enforces user_id scope. |

| ID | TC-NOT-071 |
|---|---|
| **Title** | Notification index shows only current company notifications |
| **Priority** | Critical |
| **Preconditions** | User from Company A has notification.view. Company B user has notifications. |
| **Steps** | 1. Authenticate as Company A user. 2. GET /notifications. |
| **Expected Result** | Only Company A user notifications returned. getForUser() scoped by user_id which is inherently per-company. |

| ID | TC-NOT-072 |
|---|---|
| **Title** | API: inactive company token rejected with 403 |
| **Priority** | Critical |
| **Preconditions** | User has valid Sanctum token but company.status = inactive. |
| **Steps** | 1. GET /api/v1/notifications with token. |
| **Expected Result** | HTTP 403 JSON: {success: false, message: Your company account is inactive.}. EnsureApiAccountIsActive middleware fires. |

| ID | TC-NOT-073 |
|---|---|
| **Title** | API: inactive user token rejected with 403 |
| **Priority** | Critical |
| **Preconditions** | User.status = inactive but has a valid Sanctum token. |
| **Steps** | 1. GET /api/v1/notifications with inactive user token. |
| **Expected Result** | HTTP 403 JSON: {success: false, message: Your account is inactive.}. EnsureApiAccountIsActive middleware fires. |

## Section 9 -- CSRF Protection

| ID | TC-NOT-080 |
|---|---|
| **Title** | POST /notifications/{id}/mark-read without CSRF token returns 419 |
| **Priority** | High |
| **Preconditions** | Authenticated web session. Valid own notification. |
| **Steps** | 1. POST /notifications/{id}/mark-read without _token field. |
| **Expected Result** | HTTP 419 Page Expired. Laravel CSRF middleware rejects the request. |

| ID | TC-NOT-081 |
|---|---|
| **Title** | POST /notifications/mark-all-read without CSRF token returns 419 |
| **Priority** | High |
| **Preconditions** | Authenticated web session. |
| **Steps** | 1. POST /notifications/mark-all-read without _token. |
| **Expected Result** | HTTP 419 Page Expired. |

| ID | TC-NOT-082 |
|---|---|
| **Title** | DELETE /notifications/{id} without CSRF token returns 419 |
| **Priority** | High |
| **Preconditions** | Authenticated web session. Valid own notification. |
| **Steps** | 1. DELETE /notifications/{id} without _token. |
| **Expected Result** | HTTP 419 Page Expired. Notification not deleted. |

## Section 10 -- Edge Cases

| ID | TC-NOT-090 |
|---|---|
| **Title** | Notification index renders empty state when user has no notifications |
| **Priority** | Medium |
| **Preconditions** | User has notification.view. Zero notifications for this user. |
| **Steps** | 1. GET /notifications. |
| **Expected Result** | HTTP 200. Page renders with empty state or no notifications message. No exception. |

| ID | TC-NOT-091 |
|---|---|
| **Title** | API: getForUser paginates large notification sets |
| **Priority** | Medium |
| **Preconditions** | User has notification.view and 50 notifications. Per-page default is 20. |
| **Steps** | 1. GET /api/v1/notifications. |
| **Expected Result** | HTTP 200. Response paginated. First page has 20 items. total shows 50. |

| ID | TC-NOT-092 |
|---|---|
| **Title** | Notification type field is one of allowed values |
| **Priority** | Medium |
| **Preconditions** | notify() called with type=reminder. |
| **Steps** | 1. Call NotificationService.notify() with type=reminder. |
| **Expected Result** | DB record type = reminder. Matches TYPE_REMINDER constant. |

| ID | TC-NOT-093 |
|---|---|
| **Title** | Notification priority field is one of allowed values |
| **Priority** | Medium |
| **Preconditions** | notify() called with priority=critical. |
| **Steps** | 1. Call NotificationService.notify() with priority=critical. |
| **Expected Result** | DB record priority = critical. Matches PRIORITY_CRITICAL constant. |

| ID | TC-NOT-094 |
|---|---|
| **Title** | sendWelcome() creates low-priority system notification |
| **Priority** | Low |
| **Preconditions** | New user created. |
| **Steps** | 1. Call NotificationService.sendWelcome(user). |
| **Expected Result** | DB record: type=system, priority=low, title=Welcome to RAMS. |

| ID | TC-NOT-095 |
|---|---|
| **Title** | sendPasswordChanged() creates high-priority security notification |
| **Priority** | High |
| **Preconditions** | User password changed. |
| **Steps** | 1. Call NotificationService.sendPasswordChanged(user). |
| **Expected Result** | DB record: type=security, priority=high, title=Password Changed. |

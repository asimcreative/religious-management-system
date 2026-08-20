# Quran Class Admission Form

*Issue [#28](https://github.com/asimcreative/religious-management-system/issues/28)*

## The gap this closes

Adding a member to a Quran Class picked an employee from a dropdown and added them — nothing about
*how* they were admitted (reading level, class type, whether they'd previously completed the Quran,
admission date, classes per week, where they're starting from) was ever captured. There was also no
way to tell, from the members list, which admissions had actually been filled in.

## The member is added first, the form is a follow-up, not a gate

`QuranClassMemberService::addMember()` is unchanged — same locking, same capacity check, same
already-tested behaviour. The only change is `QuranClassMemberController::store()`'s success redirect:
instead of going straight back to the members list, it goes to the new Admission Form for the employee
just added. Submitting the form saves the details; "Skip for now" returns to the members list without
them — either way the member is already in the class. This gives an identical end state to
"form-gates the add" without ever risking the add itself if the form is abandoned, and is what powers
the "Pending" badge on the members list (`quran_classes.admission_pending` / `admission_complete`,
driven by whether a `QuranClassAdmission` row exists for that membership — its mere existence *is* the
"submitted" flag, no separate boolean needed).

## "Quran Class Type" reuses `QuranDepartment`, not a new enum

`QuranDepartment` already models exactly this ("streams of Quran study" — Qaida, Nazra, Hifz), already
has an `Employee.quran_department_id` FK, and production already has all three departments seeded. The
form's "Quran Class Type" picker is a plain `QuranDepartment::active()` dropdown, not a hardcoded
3-option field.

## No conditional show/hide on "Current Starting Point"

Department names are free-text, company-editable master data, not a fixed enum — JS that hides/shows
"Lesson No." vs "Juz No." vs "Surah" based on which department string was picked would be fragile
against renames or custom departments. All three starting-point fields (`current_lesson`,
`current_sipara`, `current_surah`) are shown together, all optional, with a helper line asking to fill
whichever applies.

## "Current Starting Point" is not a snapshot — it *is* the employee's Quran Progress

The form does not invent a second, disconnected place to record where a student is up to.
`QuranClassAdmissionService::syncQuranProgress()` calls `QuranProgressService::createProgress()` or
`::updateProgress()` directly — the only two write paths into `quran_progress`, both of which already
snapshot the row into `quran_progress_history` on every write. Going around that service (writing
`QuranProgress` directly) would silently skip the history trail.

On **update** (the employee already has a progress row — e.g. re-admitted, or moving between classes),
only the fields this form actually concerns itself with are passed: `teacher_id`,
`quran_department_id`, `current_lesson`/`current_surah`/`current_sipara`, `remarks`.
`completion_percentage` and `quran_status_id` are deliberately left out of that update — a later class
admission must never reset a student's already-tracked progress or status.

On **create** (no existing progress row), `completion_percentage` starts at `0` and `quran_status_id`
defaults to the company's own "Active" `QuranStatus` (falling back to whichever active status sorts
first if it has been renamed). "Has the employee previously completed the Quran?" is a separate,
historical fact stored only on `quran_class_admissions.previously_completed_quran` — it is not the same
thing as `quran_status_id` (the student's *current* stage) and never touches it.

## Data model

`quran_class_admissions` — one optional row per membership:

| Column | Meaning |
| --- | --- |
| `quran_class_member_id` | FK into `quran_class_members`, **unique** — one admission per membership |
| `current_reading_level` | 1–10 |
| `previously_completed_quran` | boolean, default `false` |
| `admission_date` | date, not after today |
| `classes_per_week` | `5` or `6` |
| `remarks` | optional |
| `created_by` / `updated_by` | `HasAuditColumns`, same as every other master/business table |

No soft deletes — it lives and dies with its membership row (`cascadeOnDelete()`).

## Audit logging: standalone table, not a manual call

`quran_class_admissions` is a standalone table with its own `id` and its own audit columns — the same
shape as `AttendanceReason`, `QuranDepartment`, `QuranStatus`. Its create/update trail is registered
through `BusinessAuditObserver` in `AppServiceProvider::boot()`, exactly like those tables, **not** a
manual `AuditLogService::logModelChange()` call from the service.

The first draft of `QuranClassAdmissionService::store()` got this wrong — it mirrored
`QuranClassMemberService`'s manual `AuditLogService` calls, because the two services sit right next to
each other. But `QuranClassMember` (and `JamaatMember`) are membership/pivot-adjacent models that are
deliberately *excluded* from the global observer list and audited manually instead — a different
category from a standalone table like this one. The manual call was also silently wrong on its own
terms: it always logged `action: 'created'` with `oldValues: null`, even on the update branch (an
employee re-admitted, or the form re-submitted), losing the diff a genuine `'updated'` audit row would
carry. Switching to the observer fixes both problems — correct action per branch, and no duplicate
row — and a regression test (`test_submitting_admission_writes_exactly_one_audit_log_row`) locks in
"exactly one" against ever bolting a manual call back on.

## Access control

Admitting the member itself only ever needed `quran.class.update` (unchanged). Since the Admission Form
additionally *writes* `QuranProgress`, submitting it (not skipping) also requires
`quran.progress.create` or `quran.progress.update` — whichever applies, decided the same way
`QuranProgress`'s own request decides it: by whether the employee already has a progress row
(`StoreQuranClassAdmissionRequest::authorize()`). No new permission strings were needed; both already
existed. Skipping only needs `quran.class.update`, since it never touches Progress.

## Routes

```
GET  quran-classes/{quran_class}/members/{employee}/admission   quran-classes.members.admission.create
POST quran-classes/{quran_class}/members/{employee}/admission   quran-classes.members.admission.store
```

Route params are `{quran_class}`/`{employee}`, matching the existing
`QuranClassMemberController::destroy()` convention, rather than binding `QuranClassMember` directly —
the active membership is resolved internally (`QuranClassAdmissionController::activeMembership()`).

## Tests

`tests/Feature/Quran/QuranClassAdmissionTest.php` — adding a member redirects to the admission form;
the form is pre-filled with the employee/class already on record; submitting creates the admission row
and a *new* `QuranProgress` row (with a history snapshot) for an employee with none yet; submitting for
an employee who *already* has progress updates only the intended fields (`completion_percentage`/
`quran_status_id` untouched) and adds exactly one new history snapshot; submitting writes exactly one
audit log row; skipping leaves the member added with no admission row and the "Pending" badge visible;
the badge flips to "Complete" once filled in later; the store route is rejected without
`quran.progress.create`/`.update` even with `quran.class.update`, and correctly falls back to accepting
`.update` when the employee already has progress; company isolation on the admission form route.

`tests/Feature/Quran/QuranClassTest.php` — `test_add_member_to_class` and
`test_readding_an_active_member_keeps_them_active` still pass unchanged: both assert a generic
`assertRedirect()` with no specific URL, so the new redirect target doesn't affect them.

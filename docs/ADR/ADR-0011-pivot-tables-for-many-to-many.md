# ADR-0011: Pivot Tables for Many-to-Many Relationships

## Status

Accepted

## Date

2024-01-01

## Context

Several core entities in RAMS have many-to-many relationships with state:

**Employee ↔ Quran Class**

- An employee can be enrolled in a Quran class
- An employee may have been in multiple classes over time (history)
- Only one class is "active" at any time
- The enrollment has its own data: `enrolled_at`, `completed_at`, `is_active`, `notes`

**Employee ↔ Jamaat**

- An employee can be a member of a Jamaat
- An employee may have moved between Jamaats (history)
- Only one Jamaat is "active" at any time
- The membership has its own data: `joined_at`, `left_at`, `is_active`

The naive approach would be to add a `quran_class_id` or `jamaat_id` directly onto the `employees` table.

Problems with direct foreign keys on `employees`:

- Cannot track history (only the current class/Jamaat would be visible)
- Cannot record when the employee joined or left
- Cannot record who made the change (audit trail)
- Reporting on class completion rates, Jamaat membership history becomes impossible
- Changing a class assignment means overwriting the existing column — history is lost

## Decision

We use **explicit pivot tables** for all many-to-many relationships with state.

Pivot tables used:

| Pivot Table | Entities Connected | Key Extra Columns |
|-------------|-------------------|-------------------|
| `quran_class_members` | `employees` ↔ `quran_classes` | `is_active`, `enrolled_at`, `completed_at`, `notes` |
| `jamaat_members` | `employees` ↔ `jamaats` | `is_active`, `joined_at`, `left_at` |

Rules:

1. `employees` has NO `quran_class_id` column
2. `employees` has NO `jamaat_id` column
3. An employee's current class is found by querying `quran_class_members WHERE employee_id = ? AND is_active = 1`
4. History is the full set of rows for an employee in the pivot table
5. Moving an employee to a new class:
   - Sets `is_active = 0` on the current row + records `completed_at`
   - Inserts a new row with `is_active = 1` and `enrolled_at = now()`

Eloquent relationships:

```php
// Employee Model
public function quranClasses(): BelongsToMany {
    return $this->belongsToMany(QuranClass::class, 'quran_class_members')
        ->withPivot(['is_active', 'enrolled_at', 'completed_at', 'notes'])
        ->withTimestamps();
}

public function activeQuranClass(): BelongsToMany {
    return $this->quranClasses()->wherePivot('is_active', true);
}

public function jamaats(): BelongsToMany {
    return $this->belongsToMany(Jamaat::class, 'jamaat_members')
        ->withPivot(['is_active', 'joined_at', 'left_at'])
        ->withTimestamps();
}

public function activeJamaat(): BelongsToMany {
    return $this->jamaats()->wherePivot('is_active', true);
}
```

## Consequences

### Positive

- Full enrollment and membership history is preserved — no data loss when changing classes/Jamaats
- Reporting on class completion rates, average enrollment duration, and transfer history is possible
- The pivot `is_active` flag provides a clean way to query the current assignment
- Audit trails can reference the specific pivot row
- Future features (e.g., Quran completion certificates, Jamaat performance reports) have the data they need
- The design handles the case where the same employee returns to a class after leaving (multiple rows, different dates)

### Negative

- Queries for "employee's current class" require a JOIN with `WHERE is_active = 1` instead of a simple FK lookup
- Inserting a new assignment requires a two-step process (deactivate old, insert new) — must be done in a Transaction
- Without careful data integrity checks, an employee could end up with two `is_active = 1` rows (guard with unique constraint or application-level check)

### Neutral

- `company_id` is present on pivot tables to maintain tenant isolation without always joining back to the parent table
- The `is_active` flag is the source of truth for current assignment — not the latest `enrolled_at` date
- The Service layer is responsible for the deactivate-then-insert transaction; Repositories expose the query methods
- DB unique index: `UNIQUE(employee_id, quran_class_id, is_active)` where `is_active = 1` can be enforced at the DB level to prevent duplicate active assignments

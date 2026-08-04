# ADR-0010: Teacher Extends Employee (Teacher IS an Employee)

## Status

Accepted

## Date

2024-01-01

## Context

In a religious organization, a Quran Teacher is one of the most important roles.
Teachers have:

- Personal information (name, CNIC, mobile, DOB, photo)
- Employment information (branch, department, designation, joining date, employment type)
- Payroll information (salary, bank details)
- Religious qualifications (Hifz status, Tajweed level, specialization)
- Teaching-specific information (assigned classes, teaching methodology, qualification documents)

The core design question: should a Teacher be a separate entity, or should they be modeled as an Employee with an additional profile?

Two approaches considered:

**Option A: Teacher as a separate entity**

```
employees (id, name, cnic, ...)
teachers  (id, name, cnic, ...)  ← duplicates personal data
```

Problems:
- Personal and employment data is duplicated between two tables
- Changes to an employee's name/phone must be updated in two places
- Reporting across employees and teachers requires UNION queries
- Payroll processing must handle two separate flows

**Option B: Teacher IS an Employee**

```
employees (id, name, cnic, branch_id, department_id, ...)
teachers  (id, employee_id, qualification, specialization, ...)
```

Teacher profile extends the Employee record. No personal data is duplicated.
A Teacher is an Employee with a teaching profile attached.

This mirrors standard HR patterns: a nurse is an employee with a nurse profile, a pilot is an employee with a pilot profile.

## Decision

A **Teacher IS an Employee**.

The `teachers` table is a profile extension of `employees`:

```
employees
  id, company_id, employee_id (formatted), name, cnic, mobile, dob,
  branch_id, department_id, designation_id, joining_date, status, ...

teachers
  id, company_id, employee_id (FK → employees.id),
  qualification, hifz_status, tajweed_level, specialization,
  teaching_methodology, years_experience, ...
```

Rules:

- A Teacher must first exist as an Employee
- Creating a Teacher profile does not create a new Employee — it links to an existing Employee record
- Deleting an Employee cascades to delete the Teacher profile
- All personal data (name, CNIC, contact, photo) lives only on `employees`
- All teaching-specific data lives only on `teachers`
- Salary and payroll are managed through the `employees` record
- A Teacher can have a User account via `employees.user_id` (same as any other employee)

Eloquent relationship:

```php
// Teacher Model
public function employee(): BelongsTo {
    return $this->belongsTo(Employee::class);
}

// Employee Model
public function teacherProfile(): HasOne {
    return $this->hasOne(Teacher::class);
}

public function isTeacher(): bool {
    return $this->teacherProfile !== null;
}
```

## Consequences

### Positive

- No data duplication — employee name, CNIC, contact, photo stored once
- Payroll, HR, and attendance systems treat all employees uniformly
- Reporting (headcount, attendance rate, payroll) works across all employees without special cases
- Adding a Teacher profile to an existing employee is a simple one-step operation
- Removing the teaching role does not delete the employee record (employment history preserved)
- Consistent authorization — a Teacher's User account is managed the same way as any other employee

### Negative

- Creating a Teacher requires two steps: create Employee first, then add Teacher profile
- Queries for "all teachers with their personal info" always require a JOIN between `teachers` and `employees`
- Developers must be aware that Teacher data spans two tables — querying `teachers` alone is incomplete

### Neutral

- The `teachers` table has a 1:1 relationship with `employees` (one employee can have at most one teacher profile)
- This pattern can be extended to other specialized roles in the future: Imam profile, Accountant profile, etc.
- The `company_id` is present on both `employees` and `teachers` for direct query scoping without always joining

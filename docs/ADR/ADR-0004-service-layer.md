# ADR-0004: Service Layer for Business Logic

## Status

Accepted

## Date

2024-01-01

## Context

As RAMS grows, several modules require complex business logic beyond simple CRUD:

- Promoting a Quran student triggers attendance closure, progress archiving, and notifications
- Marking attendance requires checking whether the employee is active, whether they are enrolled in a class, and whether attendance has already been recorded
- Generating payroll (future) involves multi-step calculations across employees, deductions, and bonuses
- Transferring an employee between branches requires updating class memberships, Jamaat memberships, and user assignments

Without a dedicated layer, this logic would live in:

- **Controllers** — making them untestable, bloated, and tied to HTTP context
- **Models** — breaking SRP; models become "god objects" that know everything
- **Jobs** — appropriate for async work, but not synchronous business flows

The Repository Pattern (ADR-0003) handles data access cleanly.
We need a layer between Controllers and Repositories to own business rules.

Alternatives evaluated:

- **Fat Models (Active Record style)** — Business logic on the model. Easy to start, hard to maintain, breaks SRP
- **Fat Controllers** — Logic in controllers. Untestable, repeated code, HTTP context pollutes business logic
- **Action Classes** — Single-purpose classes per action. Good for simple cases, but harder to group related operations
- **Service Layer** — A service class per domain module. Well-known enterprise pattern, maps naturally to RAMS modules

## Decision

We use a **Service Layer** for all business logic.

Each module has a dedicated Service class in `app/Services/`.

Structure:

```
app/
  Services/
    EmployeeService.php
    TeacherService.php
    QuranClassService.php
    JamaatService.php
    AttendanceService.php
    ReportService.php
    ...
```

Responsibilities of a Service:

- Orchestrate business workflows (multi-step operations)
- Apply business rules and validations
- Call Repositories for data access
- Dispatch Events and Jobs
- Wrap multi-step operations in DB Transactions
- Log activity (via Spatie Activity Log)

A Service must NOT:

- Contain Eloquent queries directly (delegate to Repository)
- Know about HTTP request/response objects
- Know about Blade views or JSON formatting

Controllers call Services. Services call Repositories.

```
HTTP Request
  → FormRequest (validation)
    → Controller (HTTP handling)
      → Service (business logic)
        → Repository (data access)
          → Eloquent → Database
```

## Consequences

### Positive

- Business logic is isolated, testable, and reusable
- Controllers remain thin — they only parse input and return responses
- The same Service method can be called from a Controller, an Artisan command, a Job, or a test
- Business rules are not duplicated across controllers
- DB Transactions are managed in one place per operation
- Activity logging is centralized in the Service

### Negative

- Additional files to create and maintain for each module
- Some simple operations feel over-engineered (e.g., a simple lookup doesn't need a Service method)
- Developers must resist the urge to "shortcut" directly to Eloquent from Controllers

### Neutral

- Services are the single authority for "what happens when X occurs" — the answer is always "look in XService"
- Services depend on Repository interfaces, not concrete classes, enabling clean testing with mocks
- When a business rule changes, there is one place to change it

# ADR-0003: Repository Pattern for Data Access

## Status

Accepted

## Date

2024-01-01

## Context

In a standard Laravel CRUD application, Controllers directly call Eloquent Models.
This approach works for simple applications but creates problems as complexity grows:

- Controllers become large and contain both HTTP handling and database logic
- Business logic gets mixed with query logic
- The same query is duplicated across multiple controllers
- Unit testing requires a database connection because queries are embedded everywhere
- Swapping the data source (e.g., MySQL → PostgreSQL, or adding a cache layer) requires changes across the entire codebase

RAMS is an enterprise system with:

- 15+ modules each with CRUD operations
- Complex filtering, sorting, and pagination across most modules
- Multi-tenant scoping (every query needs `company_id`)
- Reports that aggregate data from multiple models
- A need for testability without always hitting the database

Alternatives evaluated:

- **Direct Eloquent in Controllers** — Fast to write, creates unmaintainable fat controllers
- **Direct Eloquent in Services** — Better than controllers but still couples business logic to Eloquent API
- **Query Objects** — More granular, works well for complex reads but requires significant boilerplate
- **Repository Pattern** — Provides a clean abstraction layer; widely used in Laravel enterprise apps

## Decision

We use the **Repository Pattern** for all data access.

Each module has a dedicated Repository class in `app/Repositories/`.

Structure:

```
app/
  Repositories/
    Contracts/
      EmployeeRepositoryInterface.php
      TeacherRepositoryInterface.php
      ...
    EmployeeRepository.php
    TeacherRepository.php
    ...
```

Repositories are bound in a `RepositoryServiceProvider`.
Services depend on the interface, not the concrete class.

Responsibilities of a Repository:

- All Eloquent queries (find, list, create, update, delete)
- Filtering, sorting, pagination
- Eager loading relationships
- Company scoping (every repository query respects `company_id`)

Repositories must NOT contain business rules, validations, or side-effects.

## Consequences

### Positive

- Controllers are thin — they only handle HTTP concerns
- Services contain only business logic, not query syntax
- Repository interfaces allow swapping implementations (e.g., adding a caching repository as a decorator)
- Unit tests for Services can use mock repositories without a database
- Company scoping is enforced in one place per entity — reducing the chance of data leaks
- Complex queries (with joins, subqueries, filters) are organized in one class, not scattered

### Negative

- Additional files and interfaces to maintain for each module
- Developers must know to always go through the Repository — never call Eloquent directly in Services
- For very simple CRUD, the Repository adds indirection with limited immediate benefit
- Interface binding in the service provider must be kept in sync

### Neutral

- Repositories are the single source of truth for how each entity is queried
- Adding a new filter or sort option only requires a change in one Repository class
- Future addition of a caching layer can be done transparently by wrapping the Repository

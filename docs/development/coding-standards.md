# Coding Standards

NeNe Records follows NENE2 coding standards for PHP, HTTP, and API design, with additions for the flexible entity platform.

**Framework baseline:** [NENE2 coding standards](https://github.com/hideyukiMORI/NENE2/blob/main/docs/development/coding-standards.md)

**Inheritance map:** `docs/inheritance-from-nene2.md`

## PHP Baseline (inherited)

- Target PHP `>=8.4.1 <9.0`
- `declare(strict_types=1);` on all new PHP files
- PSR-12 style
- Readonly DTOs, value objects, and enums at boundaries
- Thin controllers; use cases independent from HTTP and persistence
- Constructor injection; no service locator in domain code
- RFC 9457 Problem Details for public JSON errors

## NeNe Records Additions

### Entity platform boundaries

- **Entity types and field definitions** are persisted and validated at the API layer using a schema registry — do not rely on frontend-only schema knowledge for writes.
- **Typed field values** live in type-specific tables or documented storage adapters; avoid a single untyped key/value blob for all field types.
- **Soft delete** (`is_deleted`, `deleted_at`) should be consistent across entity tables unless an ADR documents an exception.
- **SQL stays in repositories**; use cases express business rules, not query text.

### API surface

- JSON APIs are the primary product surface.
- OpenAPI describes public request, response, and error shapes.
- MCP tools map to the same OpenAPI operations; no direct database access from MCP.
- Application-specific Problem Details `type` values use `https://nene-records.dev/problems/{problem-name}`.

### Frontend layers (future)

- **Admin frontend** owns schema editing UI and most configuration workflows.
- **Consumer views** are replaceable presentation layers over the same API.
- Backend domain logic must not depend on React or admin-specific assumptions.

### Language

- Public docs, OpenAPI text, Problem Details titles, and validation codes: **English**
- Issues, PR descriptions, commit messages, and `.cursor/rules/`: **Japanese allowed**

## Testing (inherited intent)

- Unit-test use cases without a database when practical.
- Add HTTP or contract tests for public API behavior.
- Keep tests deterministic and small.

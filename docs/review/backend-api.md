# Backend API Self-Review

Use for API endpoints, handlers, request validation, responses, and HTTP-facing behavior.

Source policies:

- `docs/development/coding-standards.md`
- `docs/inheritance-from-nene2.md`
- NENE2: `docs/development/http-runtime.md`, `request-validation.md`, `api-error-responses.md`, `domain-layer.md`

## Checklist

- [ ] Public request or response behavior is reflected in OpenAPI when stable.
- [ ] Handlers map request data to readonly DTOs before calling use cases.
- [ ] Use cases do not receive raw PSR-7 requests or unstructured request arrays.
- [ ] Business rules (ownership, state, schema invariants) live in use cases, not handlers or middleware.
- [ ] Repositories are injected through interfaces; use cases do not use PDO directly.
- [ ] SQL is confined to repository adapters.
- [ ] Validation failures use Problem Details `validation-failed` with structured `errors`.
- [ ] Application Problem Details `type` values use `https://nene-records.dev/problems/{problem-name}`.
- [ ] Public API text and error metadata are in English.
- [ ] Public errors do not expose stack traces, SQL, paths, or secrets.
- [ ] Entity writes validate against registered field schema when applicable.
- [ ] Soft-deleted records are excluded from public list/get unless explicitly documented.
- [ ] Narrowest useful verification was run (usually `composer check`).
- [ ] PR notes mention this checklist when API-facing.

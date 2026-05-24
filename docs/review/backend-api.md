# Backend API Self-Review

Use for API endpoints, handlers, use cases, validation, and HTTP behavior. Policy source: **`docs/development/backend-standards.md`**.

Also read:

- `docs/development/coding-standards.md`
- `docs/inheritance-from-nene2.md`
- NENE2: `docs/development/domain-layer.md`, `http-runtime.md`, `request-validation.md`, `api-error-responses.md`, `endpoint-scaffold.md`

## Module placement (zero tolerance)

- [ ] Domain code lives under `src/{Domain}/` — no layer-grouped roots (`Controllers/`, `UseCases/`, `Repositories/`).
- [ ] Each operation has Handler, UseCase (+ Interface), Input/Output DTOs in the correct domain folder.
- [ ] SQL exists only in `Pdo{Entity}Repository.php`; no PDO in handlers or use cases.
- [ ] Route registrar and service provider in the same domain folder.
- [ ] No imports of another domain's repositories, handlers, or internal DTOs without ADR.

## Request flow

- [ ] Handler: JSON/path parse → format validation → Input DTO → use case → JSON response.
- [ ] Path parameters read from `Router::PARAMETERS_ATTRIBUTE`, not `$request->getAttribute('id')`.
- [ ] Business rules (uniqueness, soft delete, schema invariants) in use cases, not handlers or middleware.
- [ ] Use cases do not receive PSR-7 requests or unstructured body arrays.

## Dependencies and typing

- [ ] Repositories injected via interfaces; explicit service provider wiring (no autowiring).
- [ ] DTOs and domain objects are `final readonly` where applicable.
- [ ] `declare(strict_types=1);` on all new PHP files.

## OpenAPI and errors

- [ ] Shipped endpoints have OpenAPI paths with stable `operationId`, examples, Problem Details responses.
- [ ] Application Problem Details `type` uses `https://nene-records.dev/problems/{problem-name}`.
- [ ] Validation failures use structured `errors`; public text in English.
- [ ] Responses do not expose stack traces, SQL, paths, or secrets.

## Entity platform (when applicable)

- [ ] Writes validate against API-side schema when schema registry applies.
- [ ] Soft-deleted rows excluded from list/get unless explicitly documented.
- [ ] Typed field storage — not generic untyped meta blobs.

## Testing and CI

- [ ] Use case unit tests with in-memory repository doubles.
- [ ] PDO repository tests with SQLite where repository changed.
- [ ] HTTP or contract tests for public behavior when endpoints change.
- [ ] `composer check` passes (or narrowest useful subset noted with reason).
- [ ] PHPStan level 8 clean for touched `src/` code.

## PR

- [ ] PR body includes `Self-review: backend-api` (+ `openapi-contract`, `database` when applicable).
- [ ] Reviewer rejects merge on placement or layer violations — no deferrals.

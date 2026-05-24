# ADR 0002: Entity Model and Field Definitions Registry

## Status

accepted

## Context

Phase 1 (Issue #1) introduced `entity_types`, `entities`, and `text_fields` with CRUD APIs but **no schema registry**. Writes to `text_fields` accept any `field_key`, which recreates WordPress-style untyped meta risk.

Phase 2 requires API-side field definitions so admin frontend and MCP clients cannot bypass validation.

Constraints:

- Typed value storage remains in type-specific tables (`text_fields` today; `int_fields`, etc. later).
- Domain modules stay grouped under `src/{Domain}/` per `backend-standards.md`.
- Cross-domain reads for validation must remain explicit and testable.

## Decision

### Entity relationship (Phase 1–2)

```text
entity_types (1) ──< entities (N)
entity_types (1) ──< field_defs (N)
entities (1) ──< text_fields (N)
```

| Table | Purpose |
| --- | --- |
| `entity_types` | Named kind of record (name, slug) |
| `entities` | Record instance; FK `entity_type_id`; soft delete |
| `field_defs` | Registered field key + `data_type` per entity type; soft delete |
| `text_fields` | Text value for `(entity_id, field_key)`; soft delete |

### `field_defs` columns

| Column | Type | Notes |
| --- | --- | --- |
| `id` | integer PK | |
| `entity_type_id` | FK → `entity_types.id` | |
| `field_key` | string | Unique per `entity_type_id` among active rows |
| `data_type` | string | Phase 2: `text` only enforced; future: `int`, `enum`, … |
| `is_deleted` | boolean | default false |
| `deleted_at` | datetime nullable | |

### API surface

- CRUD at `/api/v1/field-defs` (same Handler → UseCase → Repository pattern as Phase 1).
- `entity_type_id`, `field_key`, `data_type` on create/update; list supports filter by `entity_type_id`.

### Write validation (text_fields)

On **create** and **update** of `text_fields`:

1. Resolve parent `entity` → `entity_type_id`.
2. Load active `field_def` for `(entity_type_id, field_key)`.
3. If absent → reject with domain exception → **422** Problem Details (`field-key-not-registered`).
4. If present and `data_type !== 'text'` → **422** (`field-type-mismatch`).

**Cross-domain rule (explicit exception to sibling import ban):** TextField use cases may depend on **repository interfaces** of `Entity` and `FieldDef` for read-only validation. No handler or repository SQL cross-imports.

### Problem Details types (application)

- `https://nene-records.dev/problems/field-key-not-registered`
- `https://nene-records.dev/problems/field-type-mismatch`
- `https://nene-records.dev/problems/field-def-not-found`
- `https://nene-records.dev/problems/field-def-conflict` (duplicate key per entity type)

## Consequences

**Benefits**

- Schema authority moves to API; frontend becomes an editor only.
- Foundation for int/enum/bool tables in Phase 2 follow-up issues.
- Clear ER for contributors and AI agents.

**Costs**

- TextField use cases gain dependencies on Entity + FieldDef repositories.
- Additional CRUD surface and tests to maintain.

**Follow-up**

- Issue #9: implement `field_defs` CRUD + text_fields validation.
- Later Phase 2: additional typed tables; `data_type` routes writes to correct table.

## Related

- Issue: `#9`
- Supersedes: none
- Superseded by: none
- Documents: `docs/development/backend-standards.md`, `docs/explanation/product-vision.md`

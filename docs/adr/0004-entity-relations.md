# ADR 0004: Entity Relations (Typed Reference Fields)

## Status

accepted

## Context

Phase 3 adds **entity-to-entity links** distinct from tags (ADR 0003). Tags are cross-cutting labels without a typed target. Relations express schema-defined references such as “Article → Author (Person)” or “Product → Category record”.

Design review (2026-05-24) confirmed:

- **Schema approach A:** extend `field_defs` with `data_type: relation` (consistent with ADR 0002 typed registry).
- **Cardinality:** support both `one` and `many` in MVP.
- **MVP scope (Phase 3a):** attach / detach / list API + validation; **no** list filter or automatic inverse relations yet.
- **Naming:** table `entity_relations`, HTTP resource `relations`, future list query param shape `relation.{field_key}`.
- **Direction:** single directed edge only; no inverse auto-generation in MVP.

Constraints:

- Writes must validate against API-side `field_defs` (same principle as `text_fields`).
- Routes stay under `/api/v1/entities/{entityId}/…` so entity remains the aggregate entry point (same as entity tags).
- Domain modules stay grouped under `src/{Domain}/` per `backend-standards.md`.

## Decision

### ER (Phase 3a)

```text
entity_types (1) ──< field_defs (N)     [data_type=relation → target_entity_type_id, cardinality]
entity_types (1) ──< entities (N)
entities (1) ──< entity_relations (N) ──> (1) entities   [as target]
```

| Table / column | Purpose |
| --- | --- |
| `field_defs.target_entity_type_id` | Required when `data_type = relation`; FK → `entity_types.id` |
| `field_defs.cardinality` | `one` or `many` when `data_type = relation` |
| `entity_relations` | Directed link: `source_entity_id`, `target_entity_id`, `field_key` |

### `entity_relations` columns

| Column | Type | Notes |
| --- | --- | --- |
| `id` | integer PK | |
| `source_entity_id` | FK → `entities.id` | ON DELETE CASCADE |
| `target_entity_id` | FK → `entities.id` | ON DELETE CASCADE |
| `field_key` | string | Matches active `field_defs.field_key` on source entity type |

Indexes:

- Unique `(source_entity_id, target_entity_id, field_key)` — duplicate link rejected.
- Index on `(source_entity_id, field_key)` — list by field.
- Application enforces `cardinality = one` → at most one row per `(source_entity_id, field_key)`.

Nullable `target_entity_type_id` / `cardinality` on `field_defs` when `data_type` is not `relation`.

### API surface (Phase 3a)

| Operation | Route |
| --- | --- |
| List links | `GET /api/v1/entities/{entityId}/relations?field_key={key}` |
| Attach | `POST /api/v1/entities/{entityId}/relations` body `{ "field_key", "target_entity_id" }` |
| Detach | `DELETE /api/v1/entities/{entityId}/relations/{targetEntityId}?field_key={key}` |

Response list item includes at least: `field_key`, `target_entity_id`, and enough target metadata for admin UI (e.g. id; labels resolved client-side like tags).

### Write validation

On attach:

1. Source entity exists and is not soft-deleted.
2. Active `field_def` exists for `(source.entity_type_id, field_key)`.
3. `field_def.data_type === 'relation'`.
4. Target entity exists and is not soft-deleted.
5. `target.entity_type_id === field_def.target_entity_type_id`.
6. Duplicate `(source, target, field_key)` → **409**.
7. `cardinality === 'one'` and a link already exists for `(source, field_key)` → replace existing target or **409** (pick one in implementation; document in OpenAPI).

Self-reference and cycles are **not** blocked in Phase 3a.

### Domains

- `NeNeRecords\EntityRelation\` — attach, detach, list (no FieldDef CRUD duplication).
- `NeNeRecords\FieldDef\` — extend create/update validation for `relation` type.
- Cross-domain reads: EntityRelation use cases may depend on **repository interfaces** of `Entity` and `FieldDef` (same explicit exception as text field validation in ADR 0002).

### Problem Details types (application)

- `https://nene-records.dev/problems/field-key-not-registered`
- `https://nene-records.dev/problems/field-type-mismatch`
- `https://nene-records.dev/problems/relation-target-type-mismatch`
- `https://nene-records.dev/problems/relation-already-attached`
- `https://nene-records.dev/problems/relation-not-attached`
- `https://nene-records.dev/problems/relation-cardinality-exceeded`

### Deferred (Phase 3b+)

| Feature | Notes |
| --- | --- |
| List filter | `GET /api/v1/entities?relation.{field_key}={targetEntityId}` via `EntityListCriteria` extension |
| Inverse relations | e.g. “articles by author” without duplicating edges |
| Consumer view resolution | nested labels on public pages |
| MCP tools | semantic relation attach/list |

## Consequences

**Benefits**

- Relations stay typed and schema-driven; admin frontend cannot bypass validation.
- Reuses entity aggregate routing and Tags UX patterns.
- Clear extension path for query filters without new list endpoints.

**Costs**

- `field_defs` gains relation-specific columns (nullable for non-relation types).
- EntityRelation use cases depend on Entity + FieldDef repositories.
- Admin UI must load relation field defs and target entity lists per field.

**Follow-up**

- Issue #51: backend API + migrations + tests.
- Issue #52: Record detail relation attach/detach UI.

## Related

- Issue: `#51`, `#52`
- Supersedes: none
- Superseded by: none
- Documents: ADR 0002, ADR 0003, `docs/explanation/product-vision.md`

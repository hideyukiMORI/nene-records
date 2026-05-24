# ADR 0003: Tags and Entity Tags

## Status

accepted

## Context

Phase 3 (Issue #16) adds taxonomy-style classification without embedding untyped meta on entities. Tags are shared labels; `entity_tags` is a many-to-many join between `entities` and `tags`.

Constraints:

- Tag slugs are unique (same pattern as `entity_types.slug`).
- Entity list filtering by tag uses **OR** semantics (entity has **any** of the given slugs).
- Attach/detach routes live under `/api/v1/entities/{entityId}/tags` to keep entity as the aggregate entry point.

## Decision

### Tables

| Table | Purpose |
| --- | --- |
| `tags` | Tag definition (`slug`, `name`) |
| `entity_tags` | Join rows (`entity_id`, `tag_id`); unique composite index |

### API

| Area | Routes |
| --- | --- |
| Tag CRUD | `/api/v1/tags` |
| Entity tags | `GET/POST /api/v1/entities/{entityId}/tags`, `DELETE .../tags/{tagId}` |
| Filtered list | `GET /api/v1/entities?entity_type_id=&tags=&tag=` with `total` in pagination envelope |

### Domains

- `NeNeRecords\Tag\` — full Tag CRUD
- `NeNeRecords\EntityTag\` — attach/detach/list only (no Tag CRUD duplication)
- `NeNeRecords\Entity\EntityListCriteria` — list filter DTO consumed by `PdoEntityRepository`

### Errors

| Exception | HTTP |
| --- | --- |
| `TagSlugConflictException` | 409 |
| `EntityTagAlreadyAttachedException` | 409 |
| `EntityTagNotAttachedException` | 404 |

## Consequences

- Deleting a tag cascades join rows (`ON DELETE CASCADE`).
- Entity list `total` is always returned when using criteria-based queries.
- Future search/index features can extend `EntityListCriteria` without new list endpoints.

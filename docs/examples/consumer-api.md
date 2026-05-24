# Consumer API Examples

API-only patterns for building public list/detail experiences without the NeNe Records React SPA.

The JSON API is the only persistence boundary. Consumer sites — static sites, mobile apps, or alternate frontends — use the same endpoints as the bundled `/view/*` routes.

Base URL in examples: `http://localhost:8080` (local API). Adjust for your deployment.

## 1. Discover entity types

List registered content types (equivalent to `/view` index):

```bash
curl -s 'http://localhost:8080/api/v1/entity-types?limit=100&offset=0'
```

Example response:

```json
{
  "items": [
    { "id": 1, "name": "Article", "slug": "article" }
  ],
  "limit": 100,
  "offset": 0
}
```

Use `slug` to build public URLs: `/view/article`, `/view/article/42`, or your own routing scheme.

## 2. List records for a type

Resolve the type slug to `entity_type_id`, then list entities:

```bash
# entity_type_id=1 for slug "article"
curl -s 'http://localhost:8080/api/v1/entities?entity_type_id=1&limit=20&offset=0'
```

Response includes pagination metadata:

```json
{
  "items": [
    { "id": 1, "entity_type_id": 1, "is_deleted": false, "deleted_at": null }
  ],
  "limit": 20,
  "offset": 0,
  "total": 1
}
```

Optional filters (same as admin list API):

```bash
# Tag filter
curl -s 'http://localhost:8080/api/v1/entities?entity_type_id=1&tags=featured'

# Relation filter (field_key → target entity id)
curl -s 'http://localhost:8080/api/v1/entities?entity_type_id=1&relation.author=5'
```

## 3. Resolve list labels (title field)

The SPA uses the first `text` field named `title` when present. Fetch text fields for the type:

```bash
curl -s 'http://localhost:8080/api/v1/text-fields?entity_type_id=1&limit=100&offset=0'
```

Join `entity_id` with list items client-side to build card labels.

## 4. Record detail — schema and values

### Field definitions (display order + types)

```bash
curl -s 'http://localhost:8080/api/v1/field-defs?entity_type_id=1&limit=100&offset=0'
```

### Scalar values (per entity)

```bash
curl -s 'http://localhost:8080/api/v1/text-fields?entity_id=42&limit=100&offset=0'
curl -s 'http://localhost:8080/api/v1/int-fields?entity_id=42&limit=100&offset=0'
curl -s 'http://localhost:8080/api/v1/enum-fields?entity_id=42&limit=100&offset=0'
curl -s 'http://localhost:8080/api/v1/bool-fields?entity_id=42&limit=100&offset=0'
curl -s 'http://localhost:8080/api/v1/datetime-fields?entity_id=42&limit=100&offset=0'
```

### Relation fields

```bash
curl -s 'http://localhost:8080/api/v1/entities/42/relations?field_key=author'
```

Response:

```json
{
  "items": [
    { "field_key": "author", "target_entity_id": 5 }
  ]
}
```

Resolve target labels with additional entity/text-field fetches, or link to `/view/{targetTypeSlug}/{targetEntityId}`.

## 5. Minimal fetch workflow (JavaScript)

```javascript
const API = 'http://localhost:8080'

async function listPublicRecords(entityTypeSlug) {
  const types = await fetch(`${API}/api/v1/entity-types?limit=100`).then((r) => r.json())
  const type = types.items.find((t) => t.slug === entityTypeSlug)
  if (!type) return null

  const [entities, textFields] = await Promise.all([
    fetch(`${API}/api/v1/entities?entity_type_id=${type.id}&limit=20&offset=0`).then((r) =>
      r.json(),
    ),
    fetch(`${API}/api/v1/text-fields?entity_type_id=${type.id}&limit=100`).then((r) => r.json()),
  ])

  return entities.items.map((entity) => ({
    id: entity.id,
    label:
      textFields.items.find(
        (f) => f.entity_id === entity.id && f.field_key === 'title',
      )?.value ?? `Record #${entity.id}`,
  }))
}
```

## 6. Contract references

| Resource | OpenAPI tag |
| --- | --- |
| Entity types | `EntityTypes` |
| Entities | `Entities` |
| Field defs | `FieldDefs` |
| Typed fields | `TextFields`, `IntFields`, … |
| Relations | `EntityRelations` |
| Tags | `Tags`, `EntityTags` |

Full contract: [`docs/openapi/openapi.yaml`](../openapi/openapi.yaml)

MCP tools mirror the same HTTP operations: [`docs/mcp/tools.json`](../mcp/tools.json)

## 7. Replaceable presentation

- **Bundled SPA:** `/view/*` routes in `frontend/` (React consumer shell + theme).
- **Custom consumer:** any HTTP client; no admin-only bypass endpoints.
- **Auth:** public read assumes API is reachable; protect writes with NENE2 middleware (Bearer / API key) as configured in deployment.

See also: [`product-vision.md`](../explanation/product-vision.md), [`frontend-standards.md`](../development/frontend-standards.md) (Admin vs consumer).

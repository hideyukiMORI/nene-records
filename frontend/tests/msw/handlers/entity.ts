import { http, HttpResponse } from 'msw'
import { getEntityRelationLinks } from './entity-relation'
import { getEntityTagLinks } from './entity-tag'
import { getTagBySlug } from './tag'

interface EntityRecord {
  id: number
  entity_type_id: number
  is_deleted: boolean
  deleted_at: string | null
}

let nextId = 1
let items: EntityRecord[] = []

export function resetEntityStore(): void {
  nextId = 1
  items = []
}

export function seedEntities(seed: EntityRecord[]): void {
  items = [...seed]
  nextId = Math.max(0, ...seed.map((item) => item.id)) + 1
}

export function getActiveEntities(): EntityRecord[] {
  return items.filter((item) => !item.is_deleted)
}

function getEntityIdsMatchingTagSlugs(slugs: string[]): Set<number> {
  const tagIds = new Set(
    slugs.map((slug) => getTagBySlug(slug)?.id).filter((id): id is number => id !== undefined),
  )
  const entityIds = new Set<number>()

  for (const link of getEntityTagLinks()) {
    if (tagIds.has(link.tag_id)) {
      entityIds.add(link.entity_id)
    }
  }

  return entityIds
}

function parseRelationFiltersFromSearchParams(
  searchParams: URLSearchParams,
): Record<string, number> {
  const filters: Record<string, number> = {}

  searchParams.forEach((value, key) => {
    let fieldKey: string | null = null

    if (key.startsWith('relation.')) {
      fieldKey = key.slice('relation.'.length)
    } else if (key.startsWith('relation_')) {
      fieldKey = key.slice('relation_'.length)
    }

    if (fieldKey === null || fieldKey === '') {
      return
    }

    const trimmed = value.trim()

    if (trimmed === '' || !/^\d+$/.test(trimmed)) {
      return
    }

    const targetEntityId = Number(trimmed)

    if (targetEntityId > 0) {
      filters[fieldKey] = targetEntityId
    }
  })

  return filters
}

function entityMatchesRelationFilters(
  entityId: number,
  relationFilters: Record<string, number>,
): boolean {
  const links = getEntityRelationLinks()

  for (const [fieldKey, targetEntityId] of Object.entries(relationFilters)) {
    const hasLink = links.some(
      (link) =>
        link.source_entity_id === entityId &&
        link.field_key === fieldKey &&
        link.target_entity_id === targetEntityId,
    )

    if (!hasLink) {
      return false
    }
  }

  return true
}

export const entityHandlers = [
  http.get('/api/v1/entities', ({ request }) => {
    const url = new URL(request.url)
    const limit = Number(url.searchParams.get('limit') ?? '20')
    const offset = Number(url.searchParams.get('offset') ?? '0')
    const entityTypeIdParam = url.searchParams.get('entity_type_id')
    const entityTypeId = entityTypeIdParam === null ? null : Number(entityTypeIdParam)
    const tagsParam = url.searchParams.get('tags')
    const tagSlugs =
      tagsParam === null
        ? []
        : tagsParam
            .split(',')
            .map((slug) => slug.trim())
            .filter((slug) => slug.length > 0)

    const active = items.filter((item) => !item.is_deleted)
    let filtered =
      entityTypeId === null ? active : active.filter((item) => item.entity_type_id === entityTypeId)

    if (tagSlugs.length > 0) {
      const matchingEntityIds = getEntityIdsMatchingTagSlugs(tagSlugs)
      filtered = filtered.filter((item) => matchingEntityIds.has(item.id))
    }

    const relationFilters = parseRelationFiltersFromSearchParams(url.searchParams)

    if (Object.keys(relationFilters).length > 0) {
      filtered = filtered.filter((item) => entityMatchesRelationFilters(item.id, relationFilters))
    }

    return HttpResponse.json({
      items: filtered.slice(offset, offset + limit),
      limit,
      offset,
      total: filtered.length,
    })
  }),
  http.get('/api/v1/entities/:id', ({ params }) => {
    const id = Number(params.id)
    const item = items.find((entry) => entry.id === id && !entry.is_deleted)

    if (item === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(id)}`,
        },
        { status: 404 },
      )
    }

    return HttpResponse.json(item)
  }),
  http.post('/api/v1/entities', async ({ request }) => {
    const body = (await request.json()) as { entity_type_id?: number }

    if (typeof body.entity_type_id !== 'number' || body.entity_type_id < 1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: '/api/v1/entities',
          errors: [{ field: 'entity_type_id', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    const created: EntityRecord = {
      id: nextId++,
      entity_type_id: body.entity_type_id,
      is_deleted: false,
      deleted_at: null,
    }
    items = [...items, created]

    return HttpResponse.json(created, { status: 201 })
  }),
  http.delete('/api/v1/entities/:id', ({ params }) => {
    const id = Number(params.id)
    const index = items.findIndex((item) => item.id === id && !item.is_deleted)

    if (index === -1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(id)}`,
        },
        { status: 404 },
      )
    }

    items = items.map((item, itemIndex) =>
      itemIndex === index
        ? { ...item, is_deleted: true, deleted_at: new Date().toISOString() }
        : item,
    )

    return new HttpResponse(null, { status: 204 })
  }),
]

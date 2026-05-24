import { http, HttpResponse } from 'msw'

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

export const entityHandlers = [
  http.get('/api/v1/entities', ({ request }) => {
    const url = new URL(request.url)
    const limit = Number(url.searchParams.get('limit') ?? '20')
    const offset = Number(url.searchParams.get('offset') ?? '0')
    const entityTypeIdParam = url.searchParams.get('entity_type_id')
    const entityTypeId = entityTypeIdParam === null ? null : Number(entityTypeIdParam)

    const active = items.filter((item) => !item.is_deleted)
    const filtered =
      entityTypeId === null ? active : active.filter((item) => item.entity_type_id === entityTypeId)

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

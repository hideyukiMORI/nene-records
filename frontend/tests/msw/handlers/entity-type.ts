import { http, HttpResponse } from 'msw'

interface EntityTypeRecord {
  id: number
  name: string
  slug: string
  is_pinned: boolean
}

let nextId = 1
let items: EntityTypeRecord[] = []

export function resetEntityTypeStore(): void {
  nextId = 1
  items = []
}

export function seedEntityTypes(
  seed: { id: number; name: string; slug: string; is_pinned?: boolean }[],
): void {
  items = seed.map((item) => ({ ...item, is_pinned: item.is_pinned ?? false }))
  nextId = Math.max(0, ...seed.map((item) => item.id)) + 1
}

export const entityTypeHandlers = [
  http.get('/api/v1/entity-types', ({ request }) => {
    const url = new URL(request.url)
    const limit = Number(url.searchParams.get('limit') ?? '20')
    const offset = Number(url.searchParams.get('offset') ?? '0')

    return HttpResponse.json({
      items: items.slice(offset, offset + limit),
      limit,
      offset,
    })
  }),
  http.post('/api/v1/entity-types', async ({ request }) => {
    const body = (await request.json()) as { name?: string; slug?: string; is_pinned?: boolean }

    if (typeof body.name !== 'string' || body.name.trim() === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: '/api/v1/entity-types',
          errors: [{ field: 'name', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    if (items.some((item) => item.slug === body.slug)) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/conflict',
          title: 'Conflict',
          status: 409,
          instance: '/api/v1/entity-types',
        },
        { status: 409 },
      )
    }

    const created: EntityTypeRecord = {
      id: nextId++,
      name: body.name,
      slug: body.slug ?? '',
      is_pinned: body.is_pinned ?? false,
    }
    items = [...items, created]

    return HttpResponse.json(created, { status: 201 })
  }),
  http.get('/api/v1/entity-types/:id', ({ params }) => {
    const id = Number(params.id)
    const item = items.find((entry) => entry.id === id)

    if (item === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entity-types/${String(id)}`,
        },
        { status: 404 },
      )
    }

    return HttpResponse.json(item)
  }),
  http.put('/api/v1/entity-types/:id', async ({ params, request }) => {
    const id = Number(params.id)
    const index = items.findIndex((entry) => entry.id === id)

    if (index === -1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entity-types/${String(id)}`,
        },
        { status: 404 },
      )
    }

    const body = (await request.json()) as { name?: string; slug?: string; is_pinned?: boolean }

    if (typeof body.name !== 'string' || body.name.trim() === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/entity-types/${String(id)}`,
          errors: [{ field: 'name', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    if (items.some((item) => item.id !== id && item.slug === body.slug)) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/conflict',
          title: 'Conflict',
          status: 409,
          instance: `/api/v1/entity-types/${String(id)}`,
        },
        { status: 409 },
      )
    }

    const updated: EntityTypeRecord = {
      id,
      name: body.name,
      slug: body.slug ?? '',
      is_pinned: body.is_pinned ?? false,
    }
    items = [...items.slice(0, index), updated, ...items.slice(index + 1)]

    return HttpResponse.json(updated)
  }),
  http.delete('/api/v1/entity-types/:id', ({ params }) => {
    const id = Number(params.id)
    const exists = items.some((item) => item.id === id)

    if (!exists) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entity-types/${String(id)}`,
        },
        { status: 404 },
      )
    }

    items = items.filter((item) => item.id !== id)
    return new HttpResponse(null, { status: 204 })
  }),
]

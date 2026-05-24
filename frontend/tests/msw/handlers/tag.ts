import { http, HttpResponse } from 'msw'

interface TagRecord {
  id: number
  name: string
  slug: string
}

let nextId = 1
let items: TagRecord[] = []

export function resetTagStore(): void {
  nextId = 1
  items = []
}

export function seedTags(seed: TagRecord[]): void {
  items = [...seed]
  nextId = Math.max(0, ...seed.map((item) => item.id)) + 1
}

export function getTagById(id: number): TagRecord | undefined {
  return items.find((item) => item.id === id)
}

export function getTagBySlug(slug: string): TagRecord | undefined {
  return items.find((item) => item.slug === slug)
}

export const tagHandlers = [
  http.get('/api/v1/tags', ({ request }) => {
    const url = new URL(request.url)
    const limit = Number(url.searchParams.get('limit') ?? '20')
    const offset = Number(url.searchParams.get('offset') ?? '0')

    return HttpResponse.json({
      items: items.slice(offset, offset + limit),
      limit,
      offset,
    })
  }),
  http.post('/api/v1/tags', async ({ request }) => {
    const body = (await request.json()) as { name?: string; slug?: string }

    if (typeof body.name !== 'string' || body.name.trim() === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: '/api/v1/tags',
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
          instance: '/api/v1/tags',
        },
        { status: 409 },
      )
    }

    const created = {
      id: nextId++,
      name: body.name,
      slug: body.slug ?? '',
    }
    items = [...items, created]

    return HttpResponse.json(created, { status: 201 })
  }),
  http.get('/api/v1/tags/:id', ({ params }) => {
    const id = Number(params.id)
    const item = items.find((entry) => entry.id === id)

    if (item === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/tags/${String(id)}`,
        },
        { status: 404 },
      )
    }

    return HttpResponse.json(item)
  }),
  http.put('/api/v1/tags/:id', async ({ params, request }) => {
    const id = Number(params.id)
    const index = items.findIndex((entry) => entry.id === id)

    if (index === -1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/tags/${String(id)}`,
        },
        { status: 404 },
      )
    }

    const body = (await request.json()) as { name?: string; slug?: string }

    if (typeof body.name !== 'string' || body.name.trim() === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/tags/${String(id)}`,
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
          instance: `/api/v1/tags/${String(id)}`,
        },
        { status: 409 },
      )
    }

    const updated = {
      id,
      name: body.name,
      slug: body.slug ?? '',
    }
    items = [...items.slice(0, index), updated, ...items.slice(index + 1)]

    return HttpResponse.json(updated)
  }),
  http.delete('/api/v1/tags/:id', ({ params }) => {
    const id = Number(params.id)
    const exists = items.some((item) => item.id === id)

    if (!exists) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/tags/${String(id)}`,
        },
        { status: 404 },
      )
    }

    items = items.filter((item) => item.id !== id)
    return new HttpResponse(null, { status: 204 })
  }),
]

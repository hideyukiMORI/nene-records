import { http, HttpResponse } from 'msw'

interface IntFieldRecord {
  id: number
  entity_id: number
  field_key: string
  value: number
  is_deleted: boolean
}

let nextId = 1
let items: IntFieldRecord[] = []

export function resetIntFieldStore(): void {
  nextId = 1
  items = []
}

export function seedIntFields(seed: Omit<IntFieldRecord, 'is_deleted'>[]): void {
  items = seed.map((item) => ({ ...item, is_deleted: false }))
  nextId = Math.max(0, ...seed.map((item) => item.id)) + 1
}

export const intFieldHandlers = [
  http.get('/api/v1/int-fields', ({ request }) => {
    const url = new URL(request.url)
    const limit = Number(url.searchParams.get('limit') ?? '20')
    const offset = Number(url.searchParams.get('offset') ?? '0')
    const active = items.filter((item) => !item.is_deleted)

    return HttpResponse.json({
      items: active.slice(offset, offset + limit).map((item) => ({
        id: item.id,
        entity_id: item.entity_id,
        field_key: item.field_key,
        value: item.value,
      })),
      limit,
      offset,
    })
  }),
  http.post('/api/v1/int-fields', async ({ request }) => {
    const body = (await request.json()) as {
      entity_id?: number
      field_key?: string
      value?: number
    }

    if (typeof body.entity_id !== 'number' || body.entity_id < 1) {
      return HttpResponse.json({ title: 'Validation failed', status: 422 }, { status: 422 })
    }

    if (typeof body.field_key !== 'string' || body.field_key.trim() === '') {
      return HttpResponse.json({ title: 'Validation failed', status: 422 }, { status: 422 })
    }

    if (typeof body.value !== 'number') {
      return HttpResponse.json({ title: 'Validation failed', status: 422 }, { status: 422 })
    }

    const created: IntFieldRecord = {
      id: nextId++,
      entity_id: body.entity_id,
      field_key: body.field_key,
      value: body.value,
      is_deleted: false,
    }
    items = [...items, created]

    return HttpResponse.json(
      {
        id: created.id,
        entity_id: created.entity_id,
        field_key: created.field_key,
        value: created.value,
      },
      { status: 201 },
    )
  }),
  http.put('/api/v1/int-fields/:id', async ({ params, request }) => {
    const id = Number(params.id)
    const index = items.findIndex((item) => item.id === id && !item.is_deleted)

    if (index === -1) {
      return HttpResponse.json({ title: 'Not found', status: 404 }, { status: 404 })
    }

    const body = (await request.json()) as { field_key?: string; value?: number }
    const current = items[index]
    if (current === undefined) {
      return HttpResponse.json({ title: 'Not found', status: 404 }, { status: 404 })
    }

    const updated: IntFieldRecord = {
      ...current,
      field_key: body.field_key ?? current.field_key,
      value: body.value ?? current.value,
    }
    items = items.map((item, itemIndex) => (itemIndex === index ? updated : item))

    return HttpResponse.json({
      id: updated.id,
      entity_id: updated.entity_id,
      field_key: updated.field_key,
      value: updated.value,
    })
  }),
]

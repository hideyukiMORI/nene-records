import { http, HttpResponse } from 'msw'

type FieldDataType = 'text' | 'int' | 'enum' | 'bool' | 'datetime'

const FIELD_DATA_TYPES: FieldDataType[] = ['text', 'int', 'enum', 'bool', 'datetime']

function isFieldDataType(value: string): value is FieldDataType {
  return (FIELD_DATA_TYPES as string[]).includes(value)
}

interface FieldDefRecord {
  id: number
  entity_type_id: number
  field_key: string
  data_type: FieldDataType
}

let nextId = 1
let items: FieldDefRecord[] = []

export function resetFieldDefStore(): void {
  nextId = 1
  items = []
}

export function seedFieldDefs(seed: FieldDefRecord[]): void {
  items = [...seed]
  nextId = Math.max(0, ...seed.map((item) => item.id)) + 1
}

export const fieldDefHandlers = [
  http.get('/api/v1/field-defs', ({ request }) => {
    const url = new URL(request.url)
    const limit = Number(url.searchParams.get('limit') ?? '20')
    const offset = Number(url.searchParams.get('offset') ?? '0')
    const entityTypeIdParam = url.searchParams.get('entity_type_id')
    const entityTypeId = entityTypeIdParam === null ? null : Number(entityTypeIdParam)

    const filtered =
      entityTypeId === null ? items : items.filter((item) => item.entity_type_id === entityTypeId)

    return HttpResponse.json({
      items: filtered.slice(offset, offset + limit),
      limit,
      offset,
    })
  }),
  http.get('/api/v1/field-defs/:id', ({ params }) => {
    const id = Number(params.id)
    const item = items.find((entry) => entry.id === id)

    if (item === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/field-def-not-found',
          title: 'Field definition not found',
          status: 404,
          instance: `/api/v1/field-defs/${String(id)}`,
        },
        { status: 404 },
      )
    }

    return HttpResponse.json(item)
  }),
  http.post('/api/v1/field-defs', async ({ request }) => {
    const body = (await request.json()) as {
      entity_type_id?: number
      field_key?: string
      data_type?: string
    }

    if (typeof body.entity_type_id !== 'number' || body.entity_type_id < 1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: '/api/v1/field-defs',
          errors: [{ field: 'entity_type_id', message: 'Invalid', code: 'invalid' }],
        },
        { status: 422 },
      )
    }

    if (typeof body.field_key !== 'string' || body.field_key.trim() === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: '/api/v1/field-defs',
          errors: [{ field: 'field_key', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    if (typeof body.data_type !== 'string' || !isFieldDataType(body.data_type)) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: '/api/v1/field-defs',
          errors: [{ field: 'data_type', message: 'Invalid', code: 'invalid' }],
        },
        { status: 422 },
      )
    }

    const duplicate = items.some(
      (item) => item.entity_type_id === body.entity_type_id && item.field_key === body.field_key,
    )

    if (duplicate) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/field-def-conflict',
          title: 'Conflict',
          status: 409,
          instance: '/api/v1/field-defs',
        },
        { status: 409 },
      )
    }

    const created: FieldDefRecord = {
      id: nextId++,
      entity_type_id: body.entity_type_id,
      field_key: body.field_key,
      data_type: body.data_type,
    }
    items = [...items, created]

    return HttpResponse.json(created, { status: 201 })
  }),
  http.delete('/api/v1/field-defs/:id', ({ params }) => {
    const id = Number(params.id)
    const exists = items.some((item) => item.id === id)

    if (!exists) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/field-def-not-found',
          title: 'Field definition not found',
          status: 404,
          instance: `/api/v1/field-defs/${String(id)}`,
        },
        { status: 404 },
      )
    }

    items = items.filter((item) => item.id !== id)
    return new HttpResponse(null, { status: 204 })
  }),
]

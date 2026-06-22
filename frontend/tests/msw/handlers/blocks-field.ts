import { http, HttpResponse } from 'msw'

interface BlocksFieldRecord {
  id: number
  entity_id: number
  field_key: string
  value: string
  locale: string | null
  is_deleted: boolean
}

let nextId = 1
let items: BlocksFieldRecord[] = []

export const blocksFieldHandlers = [
  http.get('/api/v1/blocks-fields', ({ request }) => {
    const url = new URL(request.url)
    const limit = Number(url.searchParams.get('limit') ?? '20')
    const offset = Number(url.searchParams.get('offset') ?? '0')
    const entityIdParam = url.searchParams.get('entity_id')
    const entityId = entityIdParam === null ? null : Number(entityIdParam)

    const active = items.filter((item) => !item.is_deleted)
    const filtered =
      entityId === null ? active : active.filter((item) => item.entity_id === entityId)

    return HttpResponse.json({
      items: filtered.slice(offset, offset + limit).map((item) => ({
        id: item.id,
        entity_id: item.entity_id,
        field_key: item.field_key,
        value: item.value,
        locale: item.locale,
      })),
      limit,
      offset,
    })
  }),
  http.post('/api/v1/blocks-fields', async ({ request }) => {
    const body = (await request.json()) as {
      entity_id?: number
      field_key?: string
      value?: string
      locale?: string | null
    }

    const created: BlocksFieldRecord = {
      id: nextId++,
      entity_id: body.entity_id ?? 0,
      field_key: body.field_key ?? '',
      value: body.value ?? '[]',
      locale: body.locale ?? null,
      is_deleted: false,
    }
    items = [...items, created]

    return HttpResponse.json(
      {
        id: created.id,
        entity_id: created.entity_id,
        field_key: created.field_key,
        value: created.value,
        locale: created.locale,
      },
      { status: 201 },
    )
  }),
  http.put('/api/v1/blocks-fields/:id', async ({ params, request }) => {
    const id = Number(params.id)
    const index = items.findIndex((item) => item.id === id && !item.is_deleted)

    if (index === -1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/blocks-fields/${String(id)}`,
        },
        { status: 404 },
      )
    }

    const body = (await request.json()) as {
      field_key?: string
      value?: string
      locale?: string | null
    }
    const current = items[index]
    if (current === undefined) {
      return HttpResponse.json({ title: 'Not found' }, { status: 404 })
    }

    const updated: BlocksFieldRecord = {
      ...current,
      field_key: body.field_key ?? current.field_key,
      value: body.value ?? current.value,
      locale: body.locale === undefined ? current.locale : body.locale,
    }
    items = items.map((item, itemIndex) => (itemIndex === index ? updated : item))

    return HttpResponse.json({
      id: updated.id,
      entity_id: updated.entity_id,
      field_key: updated.field_key,
      value: updated.value,
      locale: updated.locale,
    })
  }),
]

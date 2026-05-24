import { http, HttpResponse } from 'msw'
import { getActiveEntities } from './entity'
import { getTagById } from './tag'

interface EntityTagLink {
  entity_id: number
  tag_id: number
}

let links: EntityTagLink[] = []

export function resetEntityTagStore(): void {
  links = []
}

export function seedEntityTags(seed: EntityTagLink[]): void {
  links = [...seed]
}

export function getEntityTagLinks(): EntityTagLink[] {
  return links
}

export const entityTagHandlers = [
  http.get('/api/v1/entities/:entityId/tags', ({ params }) => {
    const entityId = Number(params.entityId)
    const entity = getActiveEntities().find((item) => item.id === entityId)

    if (entity === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(entityId)}/tags`,
        },
        { status: 404 },
      )
    }

    const items = links
      .filter((link) => link.entity_id === entityId)
      .map((link) => getTagById(link.tag_id))
      .filter((tag) => tag !== undefined)

    return HttpResponse.json({ items })
  }),
  http.post('/api/v1/entities/:entityId/tags', async ({ params, request }) => {
    const entityId = Number(params.entityId)
    const entity = getActiveEntities().find((item) => item.id === entityId)

    if (entity === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(entityId)}/tags`,
        },
        { status: 404 },
      )
    }

    const body = (await request.json()) as { tag_id?: number }
    const tagId = body.tag_id

    if (typeof tagId !== 'number' || tagId < 1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/tags`,
          errors: [{ field: 'tag_id', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    const tag = getTagById(tagId)

    if (tag === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/tags/${String(tagId)}`,
        },
        { status: 404 },
      )
    }

    const exists = links.some((link) => link.entity_id === entityId && link.tag_id === tagId)

    if (exists) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/conflict',
          title: 'Conflict',
          status: 409,
          instance: `/api/v1/entities/${String(entityId)}/tags`,
        },
        { status: 409 },
      )
    }

    links = [...links, { entity_id: entityId, tag_id: tagId }]

    return HttpResponse.json(tag, { status: 201 })
  }),
  http.delete('/api/v1/entities/:entityId/tags/:tagId', ({ params }) => {
    const entityId = Number(params.entityId)
    const tagId = Number(params.tagId)
    const index = links.findIndex((link) => link.entity_id === entityId && link.tag_id === tagId)

    if (index === -1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(entityId)}/tags/${String(tagId)}`,
        },
        { status: 404 },
      )
    }

    links = links.filter((_link, linkIndex) => linkIndex !== index)

    return new HttpResponse(null, { status: 204 })
  }),
]

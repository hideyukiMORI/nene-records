import { http, HttpResponse } from 'msw'
import { getActiveEntities } from './entity'
import { findFieldDefForEntityType } from './field-def'

interface EntityRelationLink {
  source_entity_id: number
  target_entity_id: number
  field_key: string
}

let links: EntityRelationLink[] = []

export function resetEntityRelationStore(): void {
  links = []
}

export function seedEntityRelations(seed: EntityRelationLink[]): void {
  links = [...seed]
}

export function getEntityRelationLinks(): EntityRelationLink[] {
  return links
}

export const entityRelationHandlers = [
  http.get('/api/v1/entities/:entityId/relations', ({ params, request }) => {
    const entityId = Number(params.entityId)
    const entity = getActiveEntities().find((item) => item.id === entityId)

    if (entity === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 404 },
      )
    }

    const url = new URL(request.url)
    const fieldKey = url.searchParams.get('field_key')?.trim() ?? ''

    if (fieldKey === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
          errors: [{ field: 'field_key', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    const fieldDef = findFieldDefForEntityType(entity.entity_type_id, fieldKey)

    if (fieldDef === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/field-key-not-registered',
          title: 'Field Key Not Registered',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 422 },
      )
    }

    if (fieldDef.data_type !== 'relation') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/field-type-mismatch',
          title: 'Field Type Mismatch',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 422 },
      )
    }

    const items = links
      .filter((link) => link.source_entity_id === entityId && link.field_key === fieldKey)
      .map((link) => ({
        field_key: link.field_key,
        target_entity_id: link.target_entity_id,
      }))

    return HttpResponse.json({ items })
  }),
  http.post('/api/v1/entities/:entityId/relations', async ({ params, request }) => {
    const entityId = Number(params.entityId)
    const sourceEntity = getActiveEntities().find((item) => item.id === entityId)

    if (sourceEntity === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 404 },
      )
    }

    const body = (await request.json()) as {
      field_key?: string
      target_entity_id?: number
    }
    const fieldKey = body.field_key?.trim() ?? ''
    const targetEntityId = body.target_entity_id

    if (fieldKey === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
          errors: [{ field: 'field_key', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    if (typeof targetEntityId !== 'number' || targetEntityId < 1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
          errors: [{ field: 'target_entity_id', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    const fieldDef = findFieldDefForEntityType(sourceEntity.entity_type_id, fieldKey)

    if (fieldDef === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/field-key-not-registered',
          title: 'Field Key Not Registered',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 422 },
      )
    }

    if (fieldDef.data_type !== 'relation') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/field-type-mismatch',
          title: 'Field Type Mismatch',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 422 },
      )
    }

    const targetEntity = getActiveEntities().find((item) => item.id === targetEntityId)

    if (targetEntity === undefined) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/not-found',
          title: 'Not found',
          status: 404,
          instance: `/api/v1/entities/${String(targetEntityId)}`,
        },
        { status: 404 },
      )
    }

    if (
      fieldDef.target_entity_type_id !== undefined &&
      targetEntity.entity_type_id !== fieldDef.target_entity_type_id
    ) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/relation-target-type-mismatch',
          title: 'Relation Target Type Mismatch',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 422 },
      )
    }

    const exists = links.some(
      (link) =>
        link.source_entity_id === entityId &&
        link.target_entity_id === targetEntityId &&
        link.field_key === fieldKey,
    )

    if (exists) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/relation-already-attached',
          title: 'Conflict',
          status: 409,
          instance: `/api/v1/entities/${String(entityId)}/relations`,
        },
        { status: 409 },
      )
    }

    if (fieldDef.cardinality === 'one') {
      links = links.filter(
        (link) => !(link.source_entity_id === entityId && link.field_key === fieldKey),
      )
    }

    links = [
      ...links,
      { source_entity_id: entityId, target_entity_id: targetEntityId, field_key: fieldKey },
    ]

    return HttpResponse.json(
      {
        field_key: fieldKey,
        target_entity_id: targetEntityId,
      },
      { status: 201 },
    )
  }),
  http.delete('/api/v1/entities/:entityId/relations/:targetEntityId', ({ params, request }) => {
    const entityId = Number(params.entityId)
    const targetEntityId = Number(params.targetEntityId)
    const url = new URL(request.url)
    const fieldKey = url.searchParams.get('field_key')?.trim() ?? ''

    if (fieldKey === '') {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/validation-failed',
          title: 'Validation failed',
          status: 422,
          instance: `/api/v1/entities/${String(entityId)}/relations/${String(targetEntityId)}`,
          errors: [{ field: 'field_key', message: 'Required', code: 'required' }],
        },
        { status: 422 },
      )
    }

    const index = links.findIndex(
      (link) =>
        link.source_entity_id === entityId &&
        link.target_entity_id === targetEntityId &&
        link.field_key === fieldKey,
    )

    if (index === -1) {
      return HttpResponse.json(
        {
          type: 'https://nene-records.dev/problems/relation-not-attached',
          title: 'Not Found',
          status: 404,
          instance: `/api/v1/entities/${String(entityId)}/relations/${String(targetEntityId)}`,
        },
        { status: 404 },
      )
    }

    links = links.filter((_link, linkIndex) => linkIndex !== index)

    return new HttpResponse(null, { status: 204 })
  }),
]

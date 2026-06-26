import { describe, expect, it } from 'vitest'
import { toEntityId } from './ids'
import { mapCreateInputToDto, mapEntityDtoToModel, mapEntityListDtoToModel } from './mapper'

describe('entity mapper', () => {
  it('maps entity dto to model', () => {
    const model = mapEntityDtoToModel({
      id: 1,
      entity_type_id: 2,
      slug: null,
      permalink: null,
      layout: null,
      status: 'draft',
      published_at: null,
      scheduled_at: null,
      is_deleted: false,
      deleted_at: null,
      meta_title: null,
      meta_description: null,
      created_at: null,
      updated_at: null,
    })

    expect(model).toEqual({
      id: toEntityId(1),
      entityTypeId: 2,
      slug: null,
      permalink: null,
      layout: null,
      status: 'draft',
      publishedAt: null,
      scheduledAt: null,
      isDeleted: false,
      deletedAt: null,
      metaTitle: null,
      metaDescription: null,
      createdAt: null,
      updatedAt: null,
    })
  })

  it('maps list dto to model', () => {
    const model = mapEntityListDtoToModel({
      items: [
        {
          id: 3,
          entity_type_id: 1,
          slug: null,
          permalink: null,
          layout: null,
          status: 'draft',
          published_at: null,
          scheduled_at: null,
          is_deleted: false,
          deleted_at: null,
          meta_title: null,
          meta_description: null,
          created_at: null,
          updated_at: null,
        },
      ],
      limit: 20,
      offset: 0,
      total: 1,
    })

    expect(model.items).toHaveLength(1)
    expect(model.items[0]?.id).toEqual(toEntityId(3))
    expect(model.total).toBe(1)
  })

  it('maps create input to dto', () => {
    expect(mapCreateInputToDto({ entityTypeId: 5 })).toEqual({
      entity_type_id: 5,
    })
  })
})

import { describe, expect, it } from 'vitest'
import { mapCreateInputToDto, mapEntityTypeDtoToModel, mapEntityTypeListDtoToModel } from './mapper'
import { toEntityTypeId } from './ids'

describe('entity-type mapper', () => {
  it('maps entity type dto to model', () => {
    const model = mapEntityTypeDtoToModel({ id: 1, name: 'Article', slug: 'article' })

    expect(model).toEqual({
      id: toEntityTypeId(1),
      name: 'Article',
      slug: 'article',
    })
  })

  it('maps list dto to model', () => {
    const model = mapEntityTypeListDtoToModel({
      items: [{ id: 2, name: 'Page', slug: 'page' }],
      limit: 20,
      offset: 0,
    })

    expect(model.items).toHaveLength(1)
    expect(model.items[0]?.slug).toBe('page')
    expect(model.limit).toBe(20)
  })

  it('maps create input to dto', () => {
    expect(mapCreateInputToDto({ name: 'Blog', slug: 'blog' })).toEqual({
      name: 'Blog',
      slug: 'blog',
    })
  })
})

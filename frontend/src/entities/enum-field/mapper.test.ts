import { describe, expect, it } from 'vitest'
import { toEnumFieldId } from './ids'
import {
  mapCreateInputToDto,
  mapEnumFieldDtoToModel,
  mapEnumFieldListDtoToModel,
  mapUpdateInputToDto,
} from './mapper'

describe('enum-field mapper', () => {
  it('maps enum field dto to model', () => {
    const model = mapEnumFieldDtoToModel({
      id: 1,
      entity_id: 2,
      field_key: 'status',
      value: 'active',
    })

    expect(model).toEqual({
      id: toEnumFieldId(1),
      entityId: 2,
      fieldKey: 'status',
      value: 'active',
    })
  })

  it('maps create and update inputs to dto', () => {
    expect(mapCreateInputToDto({ entityId: 5, fieldKey: 'status', value: 'draft' })).toEqual({
      entity_id: 5,
      field_key: 'status',
      value: 'draft',
    })

    expect(mapUpdateInputToDto({ fieldKey: 'status', value: 'published' })).toEqual({
      field_key: 'status',
      value: 'published',
    })
  })

  it('maps list dto to model', () => {
    const model = mapEnumFieldListDtoToModel({
      items: [{ id: 3, entity_id: 1, field_key: 'status', value: 'inactive' }],
      limit: 100,
      offset: 0,
    })

    expect(model.items[0]?.value).toBe('inactive')
  })
})

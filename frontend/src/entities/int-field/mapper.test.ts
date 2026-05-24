import { describe, expect, it } from 'vitest'
import { toIntFieldId } from './ids'
import {
  mapCreateInputToDto,
  mapIntFieldDtoToModel,
  mapIntFieldListDtoToModel,
  mapUpdateInputToDto,
} from './mapper'

describe('int-field mapper', () => {
  it('maps int field dto to model', () => {
    const model = mapIntFieldDtoToModel({
      id: 1,
      entity_id: 2,
      field_key: 'count',
      value: 42,
    })

    expect(model).toEqual({
      id: toIntFieldId(1),
      entityId: 2,
      fieldKey: 'count',
      value: 42,
    })
  })

  it('maps create and update inputs to dto', () => {
    expect(mapCreateInputToDto({ entityId: 5, fieldKey: 'count', value: 7 })).toEqual({
      entity_id: 5,
      field_key: 'count',
      value: 7,
    })

    expect(mapUpdateInputToDto({ fieldKey: 'count', value: 9 })).toEqual({
      field_key: 'count',
      value: 9,
    })
  })

  it('maps list dto to model', () => {
    const model = mapIntFieldListDtoToModel({
      items: [{ id: 3, entity_id: 1, field_key: 'views', value: 100 }],
      limit: 100,
      offset: 0,
    })

    expect(model.items[0]?.value).toBe(100)
  })
})

import { describe, expect, it } from 'vitest'
import { toBoolFieldId } from './ids'
import {
  mapBoolFieldDtoToModel,
  mapBoolFieldListDtoToModel,
  mapCreateInputToDto,
  mapUpdateInputToDto,
} from './mapper'

describe('bool-field mapper', () => {
  it('maps bool field dto to model', () => {
    const model = mapBoolFieldDtoToModel({
      id: 1,
      entity_id: 2,
      field_key: 'enabled',
      value: true,
    })

    expect(model).toEqual({
      id: toBoolFieldId(1),
      entityId: 2,
      fieldKey: 'enabled',
      value: true,
    })
  })

  it('maps create and update inputs to dto', () => {
    expect(mapCreateInputToDto({ entityId: 5, fieldKey: 'enabled', value: false })).toEqual({
      entity_id: 5,
      field_key: 'enabled',
      value: false,
    })

    expect(mapUpdateInputToDto({ fieldKey: 'enabled', value: true })).toEqual({
      field_key: 'enabled',
      value: true,
    })
  })

  it('maps list dto to model', () => {
    const model = mapBoolFieldListDtoToModel({
      items: [{ id: 3, entity_id: 1, field_key: 'enabled', value: false }],
      limit: 100,
      offset: 0,
    })

    expect(model.items[0]?.value).toBe(false)
  })
})

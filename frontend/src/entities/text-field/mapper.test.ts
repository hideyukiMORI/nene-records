import { describe, expect, it } from 'vitest'
import { toTextFieldId } from './ids'
import {
  mapCreateInputToDto,
  mapTextFieldDtoToModel,
  mapTextFieldListDtoToModel,
  mapUpdateInputToDto,
} from './mapper'

describe('text-field mapper', () => {
  it('maps text field dto to model', () => {
    const model = mapTextFieldDtoToModel({
      id: 1,
      entity_id: 2,
      field_key: 'title',
      value: 'Hello',
    })

    expect(model).toEqual({
      id: toTextFieldId(1),
      entityId: 2,
      fieldKey: 'title',
      value: 'Hello',
    })
  })

  it('maps list dto to model', () => {
    const model = mapTextFieldListDtoToModel({
      items: [{ id: 3, entity_id: 1, field_key: 'body', value: 'Text' }],
      limit: 100,
      offset: 0,
    })

    expect(model.items).toHaveLength(1)
    expect(model.items[0]?.fieldKey).toBe('body')
  })

  it('maps create and update inputs to dto', () => {
    expect(mapCreateInputToDto({ entityId: 5, fieldKey: 'title', value: 'Hi' })).toEqual({
      entity_id: 5,
      field_key: 'title',
      value: 'Hi',
    })

    expect(mapUpdateInputToDto({ fieldKey: 'title', value: 'Updated' })).toEqual({
      field_key: 'title',
      value: 'Updated',
    })
  })
})

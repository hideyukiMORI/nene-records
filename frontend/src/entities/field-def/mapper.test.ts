import { describe, expect, it } from 'vitest'
import { toFieldDefId } from './ids'
import { mapCreateInputToDto, mapFieldDefDtoToModel, mapFieldDefListDtoToModel } from './mapper'

describe('field-def mapper', () => {
  it('maps field def dto to model', () => {
    const model = mapFieldDefDtoToModel({
      id: 1,
      entity_type_id: 2,
      field_key: 'title',
      data_type: 'text',
    })

    expect(model).toEqual({
      id: toFieldDefId(1),
      entityTypeId: 2,
      fieldKey: 'title',
      dataType: 'text',
      region: null,
      displayOrder: 0,
    })
  })

  it('maps list dto to model', () => {
    const model = mapFieldDefListDtoToModel({
      items: [
        {
          id: 3,
          entity_type_id: 1,
          field_key: 'body',
          data_type: 'text',
        },
      ],
      limit: 20,
      offset: 0,
    })

    expect(model.items).toHaveLength(1)
    expect(model.items[0]?.fieldKey).toBe('body')
  })

  it('maps create input to dto', () => {
    expect(
      mapCreateInputToDto({
        entityTypeId: 5,
        fieldKey: 'title',
        dataType: 'text',
      }),
    ).toEqual({
      entity_type_id: 5,
      field_key: 'title',
      data_type: 'text',
    })
  })
})

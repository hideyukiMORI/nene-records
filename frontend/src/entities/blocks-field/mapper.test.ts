import { describe, expect, it } from 'vitest'
import { toBlocksFieldId } from './ids'
import {
  mapBlocksFieldDtoToModel,
  mapBlocksFieldListDtoToModel,
  mapCreateInputToDto,
  mapUpdateInputToDto,
} from './mapper'

describe('blocks-field mapper', () => {
  it('maps blocks field dto to model', () => {
    const model = mapBlocksFieldDtoToModel({
      id: 7,
      entity_id: 3,
      field_key: 'body',
      value: '[{"id":"b1","type":"text","data":{"markdown":"hi"}}]',
      locale: null,
    })

    expect(model).toEqual({
      id: toBlocksFieldId(7),
      entityId: 3,
      fieldKey: 'body',
      value: '[{"id":"b1","type":"text","data":{"markdown":"hi"}}]',
      locale: null,
    })
  })

  it('maps list dto to model', () => {
    const model = mapBlocksFieldListDtoToModel({
      items: [{ id: 1, entity_id: 1, field_key: 'body', value: '[]', locale: null }],
      limit: 100,
      offset: 0,
    })

    expect(model.items).toHaveLength(1)
    expect(model.items[0]?.fieldKey).toBe('body')
  })

  it('maps create and update inputs to dto', () => {
    expect(
      mapCreateInputToDto({ entityId: 5, fieldKey: 'body', value: '[]', locale: null }),
    ).toEqual({ entity_id: 5, field_key: 'body', value: '[]', locale: null })

    expect(mapUpdateInputToDto({ fieldKey: 'body', value: '[]' })).toEqual({
      field_key: 'body',
      value: '[]',
    })
  })
})

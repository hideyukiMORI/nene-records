import { describe, expect, it } from 'vitest'
import { toDateTimeFieldId } from './ids'
import {
  mapCreateInputToDto,
  mapDateTimeFieldDtoToModel,
  mapDateTimeFieldListDtoToModel,
  mapUpdateInputToDto,
} from './mapper'

describe('datetime-field mapper', () => {
  it('maps datetime field dto to model', () => {
    const model = mapDateTimeFieldDtoToModel({
      id: 1,
      entity_id: 2,
      field_key: 'published_at',
      value: '2026-05-24T12:00:00.000Z',
    })

    expect(model).toEqual({
      id: toDateTimeFieldId(1),
      entityId: 2,
      fieldKey: 'published_at',
      value: '2026-05-24T12:00:00.000Z',
    })
  })

  it('maps create and update inputs to dto', () => {
    expect(
      mapCreateInputToDto({
        entityId: 5,
        fieldKey: 'published_at',
        value: '2026-05-24T12:00:00.000Z',
      }),
    ).toEqual({
      entity_id: 5,
      field_key: 'published_at',
      value: '2026-05-24T12:00:00.000Z',
    })

    expect(
      mapUpdateInputToDto({
        fieldKey: 'published_at',
        value: '2026-05-25T08:30:00.000Z',
      }),
    ).toEqual({
      field_key: 'published_at',
      value: '2026-05-25T08:30:00.000Z',
    })
  })

  it('maps list dto to model', () => {
    const model = mapDateTimeFieldListDtoToModel({
      items: [
        {
          id: 3,
          entity_id: 1,
          field_key: 'published_at',
          value: '2026-05-24T12:00:00.000Z',
        },
      ],
      limit: 100,
      offset: 0,
    })

    expect(model.items[0]?.value).toBe('2026-05-24T12:00:00.000Z')
  })
})

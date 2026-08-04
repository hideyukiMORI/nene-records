import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { act, waitFor } from '@testing-library/react'
import { useEditEntityTextFieldsPage } from './use-edit-entity-text-fields-page'
import {
  blocksFieldStoreSnapshot,
  resetBlocksFieldStore,
  seedBlocksFields,
} from '@tests/msw/handlers/blocks-field'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetFieldDefStore, seedFieldDefs } from '@tests/msw/handlers/field-def'
import { mswServer } from '@tests/msw/server'
import { renderHookWithProviders } from '@tests/render/render-with-providers'

const ENTITY_TYPE_ID = 1
const ENTITY_ID = 100
const FIELD_KEY = 'body'

const SAMPLE_DOC = '[{"id":"b1","type":"text","data":{"markdown":"hello"}}]'

function seedFixtures(): void {
  seedEntities([
    { id: ENTITY_ID, entity_type_id: ENTITY_TYPE_ID, is_deleted: false, deleted_at: null },
  ])
  seedFieldDefs([
    { id: 1, entity_type_id: ENTITY_TYPE_ID, field_key: FIELD_KEY, data_type: 'blocks' },
  ])
}

function renderPage() {
  return renderHookWithProviders(() => useEditEntityTextFieldsPage(ENTITY_TYPE_ID, ENTITY_ID))
}

describe('useEditEntityTextFieldsPage — blocks field', () => {
  beforeAll(() => {
    mswServer.listen()
  })
  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityStore()
    resetFieldDefStore()
    resetBlocksFieldStore()
  })
  afterAll(() => {
    mswServer.close()
  })

  it('loads the blocks field definition and its stored value into initialValues', async () => {
    seedFixtures()
    seedBlocksFields([
      { id: 10, entity_id: ENTITY_ID, field_key: FIELD_KEY, value: SAMPLE_DOC, locale: null },
    ])

    const { result } = renderPage()

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })
    expect(result.current.textFieldDefs.some((def) => def.fieldKey === FIELD_KEY)).toBe(true)
    expect(result.current.initialValues[FIELD_KEY]).toBe(SAMPLE_DOC)
  })

  it('updates the existing blocks row on save (no new row created)', async () => {
    seedFixtures()
    seedBlocksFields([
      { id: 10, entity_id: ENTITY_ID, field_key: FIELD_KEY, value: '[]', locale: null },
    ])

    const { result } = renderPage()
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })

    await act(async () => {
      await result.current.saveTextFields({ [FIELD_KEY]: SAMPLE_DOC })
    })

    const rows = blocksFieldStoreSnapshot()
    expect(rows).toHaveLength(1)
    expect(rows[0]?.id).toBe(10)
    expect(rows[0]?.value).toBe(SAMPLE_DOC)
  })

  it('creates a blocks row when a non-empty document is saved for a new field', async () => {
    seedFixtures()

    const { result } = renderPage()
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })

    await act(async () => {
      await result.current.saveTextFields({ [FIELD_KEY]: SAMPLE_DOC })
    })

    const rows = blocksFieldStoreSnapshot()
    expect(rows).toHaveLength(1)
    expect(rows[0]?.field_key).toBe(FIELD_KEY)
    expect(rows[0]?.value).toBe(SAMPLE_DOC)
  })

  it('skips create when an empty document is saved for a new field', async () => {
    seedFixtures()

    const { result } = renderPage()
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })

    await act(async () => {
      await result.current.saveTextFields({ [FIELD_KEY]: '' })
    })

    expect(blocksFieldStoreSnapshot()).toHaveLength(0)
  })

  it('filters the blocks value by the selected locale', async () => {
    seedFixtures()
    seedBlocksFields([
      { id: 10, entity_id: ENTITY_ID, field_key: FIELD_KEY, value: '[]', locale: null },
      { id: 11, entity_id: ENTITY_ID, field_key: FIELD_KEY, value: SAMPLE_DOC, locale: 'ja' },
    ])

    const { result } = renderPage()
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })
    // Default locale (null) → the language-neutral row.
    expect(result.current.initialValues[FIELD_KEY]).toBe('[]')

    act(() => {
      result.current.setLocale('ja')
    })

    await waitFor(() => {
      expect(result.current.initialValues[FIELD_KEY]).toBe(SAMPLE_DOC)
    })
  })
})

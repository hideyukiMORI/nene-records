import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { EntityRecordPage } from '@/pages/entity-record/EntityRecordPage'
import { resetBoolFieldStore } from '@tests/msw/handlers/bool-field'
import { resetDateTimeFieldStore } from '@tests/msw/handlers/datetime-field'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetEnumFieldStore } from '@tests/msw/handlers/enum-field'
import { resetFieldDefStore, seedFieldDefs } from '@tests/msw/handlers/field-def'
import { resetIntFieldStore } from '@tests/msw/handlers/int-field'
import { resetTextFieldStore } from '@tests/msw/handlers/text-field'
import { resetEntityTagStore } from '@tests/msw/handlers/entity-tag'
import { resetTagStore, seedTags } from '@tests/msw/handlers/tag'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderEntityRecordPage(entityTypeId = 1, entityId = 1) {
  return renderWithProviders(
    <MemoryRouter
      initialEntries={[`/entity-types/${String(entityTypeId)}/entities/${String(entityId)}`]}
    >
      <Routes>
        <Route
          path="/entity-types/:entityTypeId/entities/:entityId"
          element={<EntityRecordPage />}
        />
      </Routes>
    </MemoryRouter>,
  )
}

describe('EntityRecordPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetFieldDefStore()
    resetEntityStore()
    resetTextFieldStore()
    resetIntFieldStore()
    resetEnumFieldStore()
    resetBoolFieldStore()
    resetDateTimeFieldStore()
    resetTagStore()
    resetEntityTagStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('creates text field values on save', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])

    const user = userEvent.setup()
    renderEntityRecordPage()

    await waitFor(() => {
      expect(screen.getByLabelText('title (text)')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('title (text)'), 'Hello world')
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('title (text)')).toHaveValue('Hello world')
    })
  })

  it('updates existing text field values on save', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])

    const user = userEvent.setup()
    renderEntityRecordPage()

    await waitFor(() => {
      expect(screen.getByLabelText('title (text)')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('title (text)'), 'First title')
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('title (text)')).toHaveValue('First title')
    })

    await user.clear(screen.getByLabelText('title (text)'))
    await user.type(screen.getByLabelText('title (text)'), 'Updated title')
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('title (text)')).toHaveValue('Updated title')
    })
  })

  it('shows empty state when no text field defs exist', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])

    renderEntityRecordPage()

    expect(await screen.findByText('No editable fields defined')).toBeInTheDocument()
  })

  it('creates int field values on save', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'count', data_type: 'int' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])

    const user = userEvent.setup()
    renderEntityRecordPage()

    await waitFor(() => {
      expect(screen.getByLabelText('count (int)')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('count (int)'), '42')
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('count (int)')).toHaveValue(42)
    })
  })

  it('creates bool field values on save', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedFieldDefs([{ id: 1, entity_type_id: 1, field_key: 'enabled', data_type: 'bool' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])

    const user = userEvent.setup()
    renderEntityRecordPage()

    await waitFor(() => {
      expect(screen.getByLabelText('enabled (bool)')).toBeInTheDocument()
    })

    await user.click(screen.getByLabelText('enabled (bool)'))
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('enabled (bool)')).toBeChecked()
    })
  })

  it('attaches and removes tags on the record', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedTags([
      { id: 1, name: 'Featured', slug: 'featured' },
      { id: 2, name: 'Draft', slug: 'draft' },
    ])

    const user = userEvent.setup()
    renderEntityRecordPage()

    expect(await screen.findByText('No tags attached yet.')).toBeInTheDocument()

    await user.selectOptions(screen.getByLabelText('Add tag'), '1')
    await user.click(screen.getByRole('button', { name: 'Add tag' }))

    await waitFor(() => {
      expect(screen.getByText('Featured')).toBeInTheDocument()
      expect(screen.getByText('featured')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Remove' }))

    await waitFor(() => {
      expect(screen.queryByRole('button', { name: 'Remove' })).not.toBeInTheDocument()
      expect(screen.getByText('No tags attached yet.')).toBeInTheDocument()
    })
  })
})

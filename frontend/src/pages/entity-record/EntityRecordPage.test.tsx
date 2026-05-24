import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { EntityRecordPage } from '@/pages/entity-record/EntityRecordPage'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetFieldDefStore, seedFieldDefs } from '@tests/msw/handlers/field-def'
import { resetTextFieldStore } from '@tests/msw/handlers/text-field'
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
      expect(screen.getByLabelText('title')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('title'), 'Hello world')
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('title')).toHaveValue('Hello world')
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
      expect(screen.getByLabelText('title')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('title'), 'First title')
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('title')).toHaveValue('First title')
    })

    await user.clear(screen.getByLabelText('title'))
    await user.type(screen.getByLabelText('title'), 'Updated title')
    await user.click(screen.getByRole('button', { name: 'Save values' }))

    await waitFor(() => {
      expect(screen.getByLabelText('title')).toHaveValue('Updated title')
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

    expect(await screen.findByText('No text fields defined')).toBeInTheDocument()
  })
})

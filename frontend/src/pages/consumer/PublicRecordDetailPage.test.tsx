import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { PublicRecordDetailPage } from '@/pages/consumer/PublicRecordDetailPage'
import { resetBoolFieldStore, seedBoolFields } from '@tests/msw/handlers/bool-field'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetFieldDefStore, seedFieldDefs } from '@tests/msw/handlers/field-def'
import { resetIntFieldStore, seedIntFields } from '@tests/msw/handlers/int-field'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderDetailPage(entityTypeSlug = 'article', entityId = 1) {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/view/${entityTypeSlug}/${String(entityId)}`]}>
      <Routes>
        <Route path="/view/:entityTypeSlug/:entityId" element={<PublicRecordDetailPage />} />
      </Routes>
    </MemoryRouter>,
  )
}

describe('PublicRecordDetailPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetEntityStore()
    resetFieldDefStore()
    resetTextFieldStore()
    resetIntFieldStore()
    resetBoolFieldStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('renders read-only field values', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedFieldDefs([
      { id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' },
      { id: 2, entity_type_id: 1, field_key: 'views', data_type: 'int' },
      { id: 3, entity_type_id: 1, field_key: 'published', data_type: 'bool' },
    ])
    seedTextFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'title',
        value: 'My article',
      },
    ])
    seedIntFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'views',
        value: 42,
      },
    ])
    seedBoolFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'published',
        value: true,
      },
    ])

    renderDetailPage()

    expect(await screen.findByRole('heading', { name: 'Article' })).toBeInTheDocument()
    expect(await screen.findByText('title')).toBeInTheDocument()
    expect(screen.getByText('My article')).toBeInTheDocument()
    expect(screen.getByText('views')).toBeInTheDocument()
    expect(screen.getByText('42')).toBeInTheDocument()
    expect(screen.getByText('published')).toBeInTheDocument()
    expect(screen.getByText('Yes')).toBeInTheDocument()
  })

  it('shows not found for unknown entity type slug', async () => {
    renderDetailPage('unknown', 1)

    expect(await screen.findByText('Entity type not found')).toBeInTheDocument()
  })
})

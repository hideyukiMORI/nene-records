import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { EntityRecordsPage } from '@/pages/entity-records/EntityRecordsPage'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTagStore, seedEntityTags } from '@tests/msw/handlers/entity-tag'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetTagStore, seedTags } from '@tests/msw/handlers/tag'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderRecordsPage(entityTypeId = 1) {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/entity-types/${String(entityTypeId)}/entities`]}>
      <Routes>
        <Route path="/entity-types/:entityTypeId/entities" element={<EntityRecordsPage />} />
      </Routes>
    </MemoryRouter>,
  )
}

describe('EntityRecordsPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetEntityStore()
    resetEntityTagStore()
    resetTagStore()
    resetTextFieldStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('creates a record and lists it', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const user = userEvent.setup()
    renderRecordsPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Article' })).toBeInTheDocument()
      expect(screen.getByText('No records yet')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Create record' }))

    await waitFor(() => {
      expect(screen.getByText('Record #1')).toBeInTheDocument()
      expect(screen.getByText('1 record')).toBeInTheDocument()
    })
  })

  it('deletes a record after confirmation', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const user = userEvent.setup()
    renderRecordsPage()

    await user.click(screen.getByRole('button', { name: 'Create record' }))

    await waitFor(() => {
      expect(screen.getByText('Record #1')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Delete' }))
    const dialog = screen.getByRole('dialog')
    expect(dialog).toBeInTheDocument()

    await user.click(within(dialog).getByRole('button', { name: 'Delete' }))

    await waitFor(() => {
      expect(screen.queryByText('Record #1')).not.toBeInTheDocument()
      expect(screen.getByText('No records yet')).toBeInTheDocument()
    })
  })

  it('shows title text field value as the record label', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedTextFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'title',
        value: 'My article',
      },
    ])

    renderRecordsPage()

    expect(await screen.findByText('My article')).toBeInTheDocument()
    expect(screen.getByText('#1')).toBeInTheDocument()
  })

  it('filters records by selected tags', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedTags([
      { id: 1, name: 'Featured', slug: 'featured' },
      { id: 2, name: 'Draft', slug: 'draft' },
    ])
    seedEntities([
      { id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null },
      { id: 2, entity_type_id: 1, is_deleted: false, deleted_at: null },
    ])
    seedEntityTags([
      { entity_id: 1, tag_id: 1 },
      { entity_id: 2, tag_id: 2 },
    ])

    const user = userEvent.setup()
    renderRecordsPage()

    expect(await screen.findByText('Record #1')).toBeInTheDocument()
    expect(screen.getByText('Record #2')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Featured' }))

    await waitFor(() => {
      expect(screen.getByText('Record #1')).toBeInTheDocument()
      expect(screen.queryByText('Record #2')).not.toBeInTheDocument()
      expect(screen.getByText('1 record')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Clear' }))

    await waitFor(() => {
      expect(screen.getByText('Record #1')).toBeInTheDocument()
      expect(screen.getByText('Record #2')).toBeInTheDocument()
      expect(screen.getByText('2 records')).toBeInTheDocument()
    })
  })

  it('shows empty state when no records match the tag filter', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedTags([{ id: 1, name: 'Featured', slug: 'featured' }])
    seedEntities([{ id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null }])

    const user = userEvent.setup()
    renderRecordsPage()

    expect(await screen.findByText('Record #1')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Featured' }))

    await waitFor(() => {
      expect(screen.getByText('No matching records')).toBeInTheDocument()
    })
  })

  it('links from entity types page to records', async () => {
    const user = userEvent.setup()
    renderWithProviders(
      <MemoryRouter initialEntries={['/entity-types']}>
        <Routes>
          <Route path="/entity-types" element={<EntityTypesPage />} />
          <Route path="/entity-types/:entityTypeId/entities" element={<EntityRecordsPage />} />
        </Routes>
      </MemoryRouter>,
    )

    await user.type(screen.getByLabelText('Name'), 'Article')
    await user.type(screen.getByLabelText('Slug'), 'article')
    const form = screen.getByRole('heading', { name: 'Create entity type' }).closest('form')
    if (form === null) {
      throw new Error('Create entity type form not found')
    }
    await user.click(within(form).getByRole('button', { name: 'Create entity type' }))

    await waitFor(() => {
      expect(screen.getByText('Article')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('link', { name: 'Records' }))

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Article' })).toBeInTheDocument()
      expect(screen.getByText('article')).toBeInTheDocument()
    })
  })
})

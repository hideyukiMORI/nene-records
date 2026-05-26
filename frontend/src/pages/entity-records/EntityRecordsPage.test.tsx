import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { EntityRecordsPage } from '@/pages/entity-records/EntityRecordsPage'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityRelationStore, seedEntityRelations } from '@tests/msw/handlers/entity-relation'
import { resetEntityTagStore, seedEntityTags } from '@tests/msw/handlers/entity-tag'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetFieldDefStore, seedFieldDefs } from '@tests/msw/handlers/field-def'
import { resetTagStore, seedTags } from '@tests/msw/handlers/tag'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { clearAuthSession, seedAdminSession } from '@tests/helpers/auth-session'

function renderRecordsPage(entityTypeSlug = 'article') {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/${entityTypeSlug}`]}>
      <Routes>
        <Route path="/:entityTypeSlug" element={<EntityRecordsPage />} />
        <Route
          path="/:entityTypeSlug/:entityId"
          element={<div data-testid="entity-edit-page">Edit page</div>}
        />
      </Routes>
    </MemoryRouter>,
  )
}

describe('EntityRecordsPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  beforeEach(() => {
    seedAdminSession()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetEntityStore()
    resetEntityTagStore()
    resetEntityRelationStore()
    resetFieldDefStore()
    resetTagStore()
    resetTextFieldStore()
    clearAuthSession()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('navigates to entity edit page after clicking "New item"', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const user = userEvent.setup()
    renderRecordsPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Article' })).toBeInTheDocument()
      expect(screen.getByText('No content yet')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'New item' }))

    await waitFor(() => {
      expect(screen.getByTestId('entity-edit-page')).toBeInTheDocument()
    })
  })

  it('deletes a record after confirmation', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([{ id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null }])
    const user = userEvent.setup()
    renderRecordsPage()

    await waitFor(() => {
      expect(screen.getByText('Item #1')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Delete' }))
    const dialog = screen.getByRole('dialog')
    expect(dialog).toBeInTheDocument()

    await user.click(within(dialog).getByRole('button', { name: 'Delete' }))

    await waitFor(() => {
      expect(screen.queryByText('Item #1')).not.toBeInTheDocument()
      expect(screen.getByText('No content yet')).toBeInTheDocument()
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

    expect(await screen.findByText('Item #1')).toBeInTheDocument()
    expect(screen.getByText('Item #2')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Featured' }))

    await waitFor(() => {
      expect(screen.getByText('Item #1')).toBeInTheDocument()
      expect(screen.queryByText('Item #2')).not.toBeInTheDocument()
      expect(screen.getByText('1 item')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Clear' }))

    await waitFor(() => {
      expect(screen.getByText('Item #1')).toBeInTheDocument()
      expect(screen.getByText('Item #2')).toBeInTheDocument()
      expect(screen.getByText('2 items')).toBeInTheDocument()
    })
  })

  it('shows empty state when no records match the tag filter', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedTags([{ id: 1, name: 'Featured', slug: 'featured' }])
    seedEntities([{ id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null }])

    const user = userEvent.setup()
    renderRecordsPage()

    expect(await screen.findByText('Item #1')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Featured' }))

    await waitFor(() => {
      expect(screen.getByText('No matching items')).toBeInTheDocument()
    })
  })

  it('filters records by selected relation target', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Author', slug: 'author' },
    ])
    seedFieldDefs([
      {
        id: 1,
        entity_type_id: 1,
        field_key: 'author',
        data_type: 'relation',
        target_entity_type_id: 2,
        cardinality: 'one',
      },
    ])
    seedEntities([
      { id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null },
      { id: 2, entity_type_id: 1, is_deleted: false, deleted_at: null },
      { id: 10, entity_type_id: 2, is_deleted: false, deleted_at: null },
      { id: 11, entity_type_id: 2, is_deleted: false, deleted_at: null },
    ])
    seedEntityRelations([
      { source_entity_id: 1, target_entity_id: 10, field_key: 'author' },
      { source_entity_id: 2, target_entity_id: 11, field_key: 'author' },
    ])

    const user = userEvent.setup()
    renderRecordsPage()

    expect(await screen.findByText('Item #1')).toBeInTheDocument()
    expect(screen.getByText('Item #2')).toBeInTheDocument()

    await user.selectOptions(await screen.findByLabelText('author'), '10')

    await waitFor(() => {
      expect(screen.getByText('Item #1')).toBeInTheDocument()
      expect(screen.queryByText('Item #2')).not.toBeInTheDocument()
      expect(screen.getByText('1 item')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Clear' }))

    await waitFor(() => {
      expect(screen.getByText('Item #1')).toBeInTheDocument()
      expect(screen.getByText('Item #2')).toBeInTheDocument()
      expect(screen.getByText('2 items')).toBeInTheDocument()
    })
  })

  it('shows empty state when no records match the relation filter', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Author', slug: 'author' },
    ])
    seedFieldDefs([
      {
        id: 1,
        entity_type_id: 1,
        field_key: 'author',
        data_type: 'relation',
        target_entity_type_id: 2,
        cardinality: 'one',
      },
    ])
    seedEntities([
      { id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null },
      { id: 10, entity_type_id: 2, is_deleted: false, deleted_at: null },
    ])

    const user = userEvent.setup()
    renderRecordsPage()

    expect(await screen.findByText('Item #1')).toBeInTheDocument()

    await user.selectOptions(await screen.findByLabelText('author'), '10')

    await waitFor(() => {
      expect(screen.getByText('No matching items')).toBeInTheDocument()
    })
  })

  it('links from entity types page to records', async () => {
    const user = userEvent.setup()
    renderWithProviders(
      <MemoryRouter initialEntries={['/entity-types']}>
        <Routes>
          <Route path="/entity-types" element={<EntityTypesPage />} />
          <Route path="/:entityTypeSlug" element={<EntityRecordsPage />} />
        </Routes>
      </MemoryRouter>,
    )

    await user.type(screen.getByLabelText('Name'), 'Article')
    await user.type(screen.getByLabelText('Slug'), 'article')
    const form = screen.getByRole('heading', { name: 'Create content type' }).closest('form')
    if (form === null) {
      throw new Error('Create content type form not found')
    }
    await user.click(within(form).getByRole('button', { name: 'Create content type' }))

    await waitFor(() => {
      expect(screen.getByText('Article')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('link', { name: 'Contents' }))

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Article' })).toBeInTheDocument()
    })
  })
})

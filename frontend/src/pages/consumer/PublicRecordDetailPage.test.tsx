import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { PublicRecordDetailPage } from '@/pages/consumer/PublicRecordDetailPage'
import { resetBoolFieldStore, seedBoolFields } from '@tests/msw/handlers/bool-field'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityRelationStore, seedEntityRelations } from '@tests/msw/handlers/entity-relation'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetFieldDefStore, seedFieldDefs } from '@tests/msw/handlers/field-def'
import { resetIntFieldStore, seedIntFields } from '@tests/msw/handlers/int-field'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { PublicSiteTestProvider } from '@tests/render/PublicSiteTestProvider'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderDetailPage(entityTypeSlug = 'article', entityId = 1) {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/${entityTypeSlug}/${String(entityId)}`]}>
      <Routes>
        <Route element={<PublicSiteTestProvider />}>
          <Route path="/:entityTypeSlug/*" element={<PublicRecordDetailPage />} />
        </Route>
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
    resetEntityRelationStore()
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

    // The record title is lifted into the masthead h1 (and dropped from the body).
    expect(await screen.findByRole('heading', { level: 1, name: 'My article' })).toBeInTheDocument()
    expect(screen.getByText('views')).toBeInTheDocument()
    expect(screen.getByText('42')).toBeInTheDocument()
    expect(screen.getByText('published')).toBeInTheDocument()
    expect(screen.getByText('Yes')).toBeInTheDocument()
  })

  it('renders relation fields as links to target records', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Author', slug: 'author' },
    ])
    seedEntities([
      { id: 1, entity_type_id: 1, is_deleted: false, deleted_at: null },
      { id: 2, entity_type_id: 2, is_deleted: false, deleted_at: null },
    ])
    seedFieldDefs([
      { id: 1, entity_type_id: 1, field_key: 'title', data_type: 'text' },
      {
        id: 2,
        entity_type_id: 1,
        field_key: 'author',
        data_type: 'relation',
        target_entity_type_id: 2,
        cardinality: 'one',
      },
    ])
    seedTextFields([
      { id: 1, entity_id: 1, field_key: 'title', value: 'My article' },
      { id: 2, entity_id: 2, field_key: 'title', value: 'Alice' },
    ])
    seedEntityRelations([{ source_entity_id: 1, target_entity_id: 2, field_key: 'author' }])

    renderDetailPage('article', 1)

    expect(await screen.findByText('My article')).toBeInTheDocument()
    expect(await screen.findByText('author')).toBeInTheDocument()

    const authorLink = screen.getByRole('link', { name: 'Alice' })
    expect(authorLink).toHaveAttribute('href', '/author/2')
  })

  it('renders body text fields as markdown', async () => {
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
      { id: 2, entity_type_id: 1, field_key: 'body', data_type: 'text' },
    ])
    seedTextFields([
      {
        id: 1,
        entity_id: 1,
        field_key: 'title',
        value: 'My article',
      },
      {
        id: 2,
        entity_id: 1,
        field_key: 'body',
        value: '## Intro\n\n**bold** paragraph',
      },
    ])

    renderDetailPage()

    expect(await screen.findByRole('heading', { level: 2, name: 'Intro' })).toBeInTheDocument()
    expect(screen.getByText('bold')).toBeInTheDocument()
  })

  it('shows not found for unknown entity type slug', async () => {
    renderDetailPage('unknown', 1)

    expect(await screen.findByText('Not found')).toBeInTheDocument()
  })
})

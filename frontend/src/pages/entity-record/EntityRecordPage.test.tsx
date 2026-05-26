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
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { resetEntityRelationStore, seedEntityRelations } from '@tests/msw/handlers/entity-relation'
import { resetEntityTagStore } from '@tests/msw/handlers/entity-tag'
import { resetTagStore, seedTags } from '@tests/msw/handlers/tag'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderEntityRecordPage(entityTypeSlug = 'article', entityId = 1) {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/${entityTypeSlug}/${String(entityId)}`]}>
      <Routes>
        <Route path="/:entityTypeSlug/:entityId" element={<EntityRecordPage />} />
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
    resetEntityRelationStore()
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
    await user.click(screen.getByRole('button', { name: 'Save' }))

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
    await user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(screen.getByLabelText('title (text)')).toHaveValue('First title')
    })

    await user.clear(screen.getByLabelText('title (text)'))
    await user.type(screen.getByLabelText('title (text)'), 'Updated title')
    await user.click(screen.getByRole('button', { name: 'Save' }))

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

    expect(await screen.findByText('No fields defined yet')).toBeInTheDocument()
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
    await user.click(screen.getByRole('button', { name: 'Save' }))

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
    await user.click(screen.getByRole('button', { name: 'Save' }))

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

  it('attaches and removes relation targets on the record', async () => {
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
      { id: 2, entity_type_id: 2, is_deleted: false, deleted_at: null },
      { id: 3, entity_type_id: 2, is_deleted: false, deleted_at: null },
    ])
    seedTextFields([
      { id: 1, entity_id: 2, field_key: 'title', value: 'Alice' },
      { id: 2, entity_id: 3, field_key: 'title', value: 'Bob' },
    ])

    const user = userEvent.setup()
    renderEntityRecordPage()

    expect(await screen.findByRole('heading', { name: 'Relations' })).toBeInTheDocument()
    expect(await screen.findByText('No targets linked yet.')).toBeInTheDocument()

    await user.selectOptions(screen.getByLabelText('Set target'), '2')
    await user.click(screen.getByRole('button', { name: 'Set target' }))

    await waitFor(() => {
      expect(screen.getByText('Alice')).toBeInTheDocument()
      expect(screen.getByText('#2')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Remove' }))

    await waitFor(() => {
      expect(screen.queryByRole('button', { name: 'Remove' })).not.toBeInTheDocument()
      expect(screen.getByText('No targets linked yet.')).toBeInTheDocument()
    })
  })

  it('replaces one-cardinality relation target when setting a new target', async () => {
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
      { id: 2, entity_type_id: 2, is_deleted: false, deleted_at: null },
      { id: 3, entity_type_id: 2, is_deleted: false, deleted_at: null },
    ])
    seedTextFields([
      { id: 1, entity_id: 2, field_key: 'title', value: 'Alice' },
      { id: 2, entity_id: 3, field_key: 'title', value: 'Bob' },
    ])

    const user = userEvent.setup()
    renderEntityRecordPage()

    expect(await screen.findByLabelText('Set target')).toBeInTheDocument()

    await user.selectOptions(screen.getByLabelText('Set target'), '2')
    await user.click(screen.getByRole('button', { name: 'Set target' }))

    await waitFor(() => {
      expect(screen.getByText('Alice')).toBeInTheDocument()
    })

    await user.selectOptions(screen.getByLabelText('Set target'), '3')
    await user.click(screen.getByRole('button', { name: 'Set target' }))

    await waitFor(() => {
      expect(screen.queryByText('#2')).not.toBeInTheDocument()
      expect(screen.getByText('Bob')).toBeInTheDocument()
      expect(screen.getByText('#3')).toBeInTheDocument()
    })
  })

  it('shows source records that reference this target via inverse relations', async () => {
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
      { id: 2, entity_type_id: 2, is_deleted: false, deleted_at: null },
      { id: 3, entity_type_id: 1, is_deleted: false, deleted_at: null },
      { id: 4, entity_type_id: 2, is_deleted: false, deleted_at: null },
    ])
    seedEntityRelations([
      { source_entity_id: 1, target_entity_id: 2, field_key: 'author' },
      { source_entity_id: 3, target_entity_id: 4, field_key: 'author' },
    ])
    seedTextFields([
      { id: 1, entity_id: 1, field_key: 'title', value: 'My article' },
      { id: 2, entity_id: 2, field_key: 'title', value: 'Alice' },
      { id: 3, entity_id: 3, field_key: 'title', value: 'Other article' },
    ])

    renderEntityRecordPage('author', 2)

    expect(await screen.findByRole('heading', { name: 'Referenced by' })).toBeInTheDocument()
    expect(await screen.findByText('Article · author')).toBeInTheDocument()
    expect(await screen.findByText('My article')).toBeInTheDocument()
    expect(screen.queryByText('Other article')).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Open' })).toHaveAttribute('href', '/article/1')
  })
})

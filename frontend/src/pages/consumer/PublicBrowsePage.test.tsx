import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { PublicBrowsePage } from '@/pages/consumer/PublicBrowsePage'
import { PublicRecordDetailPage } from '@/pages/consumer/PublicRecordDetailPage'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderBrowsePage(slug = 'article') {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/view/${slug}`]}>
      <Routes>
        <Route path="/view/:entityTypeSlug" element={<PublicBrowsePage />} />
        <Route path="/view/:entityTypeSlug/:entityId" element={<PublicRecordDetailPage />} />
      </Routes>
    </MemoryRouter>,
  )
}

describe('PublicBrowsePage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetEntityStore()
    resetTextFieldStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('lists records with title labels for a public entity type', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        is_deleted: false,
        deleted_at: null,
      },
      {
        id: 2,
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
        value: 'First post',
      },
      {
        id: 2,
        entity_id: 2,
        field_key: 'title',
        value: 'Second post',
      },
    ])

    renderBrowsePage()

    expect(await screen.findByText('2 records')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'First post' })).toHaveAttribute(
      'href',
      '/view/article/1',
    )
    expect(screen.getByRole('link', { name: 'Second post' })).toHaveAttribute(
      'href',
      '/view/article/2',
    )
  })

  it('shows empty state for unknown entity type slug', async () => {
    renderBrowsePage('missing-type')

    expect(await screen.findByText('Entity type not found')).toBeInTheDocument()
    expect(screen.getByText('No public content for "missing-type".')).toBeInTheDocument()
  })

  it('navigates to record detail from the list', async () => {
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
        value: 'Hello world',
      },
    ])

    const user = userEvent.setup()
    renderBrowsePage()

    await user.click(await screen.findByRole('link', { name: 'Hello world' }))

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Back to Article' })).toBeInTheDocument()
    })
  })
})

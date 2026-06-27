import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { http, HttpResponse } from 'msw'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { PublicBrowsePage } from '@/pages/consumer/PublicBrowsePage'
import { PublicIndexPage } from '@/pages/consumer/PublicIndexPage'
import { PublicRecordDetailPage } from '@/pages/consumer/PublicRecordDetailPage'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { resetTextFieldStore, seedTextFields } from '@tests/msw/handlers/text-field'
import { mswServer } from '@tests/msw/server'
import { PublicSiteTestProvider } from '@tests/render/PublicSiteTestProvider'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderBrowsePage(initialEntry = '/article') {
  return renderWithProviders(
    <MemoryRouter initialEntries={[initialEntry]}>
      <Routes>
        <Route element={<PublicSiteTestProvider />}>
          <Route path="/" element={<PublicIndexPage />} />
          <Route path="/:entityTypeSlug" element={<PublicBrowsePage />} />
          <Route path="/:entityTypeSlug/:entityId" element={<PublicRecordDetailPage />} />
        </Route>
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
        status: 'published',
        is_deleted: false,
        deleted_at: null,
      },
      {
        id: 2,
        entity_type_id: 1,
        status: 'published',
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
    expect(screen.getByRole('link', { name: 'First post' })).toHaveAttribute('href', '/article/1')
    expect(screen.getByRole('link', { name: 'Second post' })).toHaveAttribute('href', '/article/2')
  })

  it('treats an unknown slug as a custom permalink, showing not found when unresolved (#656)', async () => {
    renderBrowsePage('/missing-type')

    expect(await screen.findByText('Record not found')).toBeInTheDocument()
  })

  it('navigates to record detail from the list', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        status: 'published',
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
      expect(screen.getByRole('link', { name: 'Back to Article' })).toBeInTheDocument()
    })
  })

  it('renders a custom-permalink page by resolving its path (#656)', async () => {
    mswServer.use(
      http.get('/api/v1/public/records/resolve', () =>
        HttpResponse.json({ found: true, entityTypeSlug: 'article', entityId: 1 }),
      ),
    )
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities([
      {
        id: 1,
        entity_type_id: 1,
        permalink: '/about',
        status: 'published',
        is_deleted: false,
        deleted_at: null,
      },
    ])
    seedTextFields([{ id: 1, entity_id: 1, field_key: 'title', value: 'About Us' }])

    renderBrowsePage('/about')

    // The custom-permalink record resolves → renders the detail in place (no
    // redirect, since its canonical IS the permalink — exercises the #656 fix).
    expect(await screen.findByRole('link', { name: 'Back to Article' })).toBeInTheDocument()
  })

  it('paginates records with offset query param', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    seedEntities(
      Array.from({ length: 21 }, (_, index) => ({
        id: index + 1,
        entity_type_id: 1,
        status: 'published' as const,
        is_deleted: false,
        deleted_at: null,
      })),
    )
    seedTextFields(
      Array.from({ length: 21 }, (_, index) => ({
        id: index + 1,
        entity_id: index + 1,
        field_key: 'title',
        value: `Post ${String(index + 1)}`,
      })),
    )

    const user = userEvent.setup()
    renderBrowsePage('/article')

    expect(await screen.findByText('21 records · showing 1–20')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Post 1' })).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: 'Post 21' })).not.toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Next page' }))

    await waitFor(() => {
      expect(screen.getByText('21 records · showing 21–21')).toBeInTheDocument()
    })
    expect(screen.getByRole('link', { name: 'Post 21' })).toBeInTheDocument()
  })
})

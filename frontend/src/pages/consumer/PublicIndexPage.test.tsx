import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { PublicIndexPage } from '@/pages/consumer/PublicIndexPage'
import { resetEntityStore, seedEntities } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { mswServer } from '@tests/msw/server'
import { PublicSiteTestProvider } from '@tests/render/PublicSiteTestProvider'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderIndexPage() {
  return renderWithProviders(
    <MemoryRouter initialEntries={['/']}>
      <Routes>
        <Route element={<PublicSiteTestProvider />}>
          <Route path="/" element={<PublicIndexPage />} />
        </Route>
      </Routes>
    </MemoryRouter>,
  )
}

function publishedRecord(over: {
  id: number
  entity_type_id: number
  slug: string
  published_at: string
}) {
  return {
    ...over,
    status: 'published' as const,
    is_deleted: false,
    deleted_at: null,
  }
}

describe('PublicIndexPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetEntityStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('renders the hero masthead and the color theme switch', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])

    renderIndexPage()

    expect(
      await screen.findByRole('heading', { level: 1, name: /one record/i }),
    ).toBeInTheDocument()
    const themeGroup = screen.getByRole('group', { name: 'Color theme' })
    expect(themeGroup).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Light theme' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Dark theme' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Match system' })).toBeInTheDocument()
  })

  it('lists entity-type entrances linking to public browse routes', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Product', slug: 'product' },
    ])

    renderIndexPage()

    // Wait on a typecard slug, which only renders once entity types have loaded
    // (the hero CTA "Browse by type" appears immediately and would race the data).
    expect(await screen.findByText('/article')).toBeInTheDocument()
    const links = screen.getAllByRole('link')
    expect(links.some((link) => link.getAttribute('href') === '/article')).toBe(true)
    expect(links.some((link) => link.getAttribute('href') === '/product')).toBe(true)
  })

  it('features the newest published record and grids the rest', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Product', slug: 'product' },
    ])
    seedEntities([
      publishedRecord({
        id: 10,
        entity_type_id: 1,
        slug: 'spring-release-notes',
        published_at: '2026-06-08T00:00:00Z',
      }),
      publishedRecord({
        id: 11,
        entity_type_id: 2,
        slug: 'new-arrival',
        published_at: '2026-06-01T00:00:00Z',
      }),
    ])

    renderIndexPage()

    const featuredLink = await screen.findByRole('link', { name: 'Spring Release Notes' })
    expect(featuredLink).toHaveAttribute('href', '/article/10')
    expect(screen.getByRole('link', { name: 'New Arrival' })).toHaveAttribute('href', '/product/11')
  })

  it('shows the empty feed state when nothing is published', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])

    renderIndexPage()

    expect(await screen.findByText('No published records yet')).toBeInTheDocument()
  })
})

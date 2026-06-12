import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { PublicIndexPage } from '@/pages/consumer/PublicIndexPage'
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

describe('PublicIndexPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('lists entity types with links to public browse routes', async () => {
    seedEntityTypes([
      { id: 1, name: 'Article', slug: 'article' },
      { id: 2, name: 'Product', slug: 'product' },
    ])

    renderIndexPage()

    expect(await screen.findByRole('link', { name: 'Article' })).toHaveAttribute('href', '/article')
    expect(screen.getByRole('link', { name: 'Product' })).toHaveAttribute('href', '/product')
    expect(screen.getByText('/article')).toBeInTheDocument()
  })

  it('shows empty state when no entity types exist', async () => {
    renderIndexPage()

    expect(await screen.findByText('No content types yet')).toBeInTheDocument()
  })
})

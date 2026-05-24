import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { EntityRecordsPage } from '@/pages/entity-records/EntityRecordsPage'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { resetEntityStore } from '@tests/msw/handlers/entity'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
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

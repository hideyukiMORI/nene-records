import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { resetEntityTypeStore } from '@tests/msw/handlers/entity-type'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function getCreateForm(): HTMLElement {
  const form = screen.getByRole('heading', { name: 'Create entity type' }).closest('form')
  if (form === null) {
    throw new Error('Create entity type form not found')
  }
  return form
}

describe('EntityTypesPage', () => {
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

  it('creates an entity type and lists it', async () => {
    const user = userEvent.setup()
    renderWithProviders(<EntityTypesPage />)

    await waitFor(() => {
      expect(screen.getByText('No entity types yet')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('Name'), 'Article')
    await user.type(screen.getByLabelText('Slug'), 'article')
    const form = getCreateForm()
    await user.click(within(form).getByRole('button', { name: 'Create entity type' }))

    await waitFor(() => {
      expect(screen.getByText('Article')).toBeInTheDocument()
      expect(screen.getByText('article')).toBeInTheDocument()
    })
  })

  it('shows validation errors for invalid slug', async () => {
    const user = userEvent.setup()
    renderWithProviders(<EntityTypesPage />)

    await user.type(screen.getByLabelText('Name'), 'Bad Slug')
    await user.type(screen.getByLabelText('Slug'), 'Bad Slug')
    const form = getCreateForm()
    await user.click(within(form).getByRole('button', { name: 'Create entity type' }))

    expect(
      await screen.findByText('Use lowercase letters, numbers, and hyphens'),
    ).toBeInTheDocument()
  })

  it('deletes an entity type after confirmation', async () => {
    const user = userEvent.setup()
    renderWithProviders(<EntityTypesPage />)

    await user.type(screen.getByLabelText('Name'), 'Page')
    await user.type(screen.getByLabelText('Slug'), 'page')
    const form = getCreateForm()
    await user.click(within(form).getByRole('button', { name: 'Create entity type' }))

    await waitFor(() => {
      expect(screen.getByText('Page')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Delete' }))
    const dialog = screen.getByRole('dialog')
    expect(dialog).toBeInTheDocument()

    await user.click(within(dialog).getByRole('button', { name: 'Delete' }))

    await waitFor(() => {
      expect(screen.queryByText('Page')).not.toBeInTheDocument()
    })
  })
})

import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { EntityTypesPage } from '@/pages/entity-types/EntityTypesPage'
import { resetEntityTypeStore } from '@tests/msw/handlers/entity-type'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { clearAuthSession, seedAdminSession } from '@tests/helpers/auth-session'

function renderEntityTypesPage() {
  return renderWithProviders(
    <MemoryRouter>
      <EntityTypesPage />
    </MemoryRouter>,
  )
}

function getCreateForm(): HTMLElement {
  const form = screen.getByRole('heading', { name: 'Create entity type' }).closest('form')
  if (form === null) {
    throw new Error('Create entity type form not found')
  }
  return form
}

function getEditForm(): HTMLElement {
  const form = screen.getByRole('heading', { name: 'Edit entity type' }).closest('form')
  if (form === null) {
    throw new Error('Edit entity type form not found')
  }
  return form
}

describe('EntityTypesPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  beforeEach(() => {
    seedAdminSession()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    clearAuthSession()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('creates an entity type and lists it', async () => {
    const user = userEvent.setup()
    renderEntityTypesPage()

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
    renderEntityTypesPage()

    await user.type(screen.getByLabelText('Name'), 'Bad Slug')
    await user.type(screen.getByLabelText('Slug'), 'Bad Slug')
    const form = getCreateForm()
    await user.click(within(form).getByRole('button', { name: 'Create entity type' }))

    expect(
      await screen.findByText('Use lowercase letters, numbers, and hyphens'),
    ).toBeInTheDocument()
  })

  it('updates an entity type name and slug', async () => {
    const user = userEvent.setup()
    renderEntityTypesPage()

    await user.type(screen.getByLabelText('Name'), 'Article')
    await user.type(screen.getByLabelText('Slug'), 'article')
    const createForm = getCreateForm()
    await user.click(within(createForm).getByRole('button', { name: 'Create entity type' }))

    await waitFor(() => {
      expect(screen.getByText('Article')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Edit' }))

    const editForm = getEditForm()
    const nameInput = within(editForm).getByLabelText('Name')
    const slugInput = within(editForm).getByLabelText('Slug')

    await user.clear(nameInput)
    await user.type(nameInput, 'Blog post')
    await user.clear(slugInput)
    await user.type(slugInput, 'blog-post')
    await user.click(within(editForm).getByRole('button', { name: 'Save changes' }))

    await waitFor(() => {
      expect(screen.getByText('Blog post')).toBeInTheDocument()
      expect(screen.getByText('blog-post')).toBeInTheDocument()
      expect(screen.queryByText('Article')).not.toBeInTheDocument()
      expect(screen.queryByText('article')).not.toBeInTheDocument()
    })
  })

  it('deletes an entity type after confirmation', async () => {
    const user = userEvent.setup()
    renderEntityTypesPage()

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

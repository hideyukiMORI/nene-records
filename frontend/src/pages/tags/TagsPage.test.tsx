import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { TagsPage } from '@/pages/tags/TagsPage'
import { resetTagStore } from '@tests/msw/handlers/tag'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderTagsPage() {
  return renderWithProviders(
    <MemoryRouter>
      <TagsPage />
    </MemoryRouter>,
  )
}

function getCreateForm(): HTMLElement {
  const form = screen.getByRole('heading', { name: 'Create tag' }).closest('form')
  if (form === null) {
    throw new Error('Create tag form not found')
  }
  return form
}

function getEditForm(): HTMLElement {
  const form = screen.getByRole('heading', { name: 'Edit tag' }).closest('form')
  if (form === null) {
    throw new Error('Edit tag form not found')
  }
  return form
}

describe('TagsPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetTagStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('creates a tag and lists it', async () => {
    const user = userEvent.setup()
    renderTagsPage()

    await waitFor(() => {
      expect(screen.getByText('No tags yet')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('Name'), 'Featured')
    await user.type(screen.getByLabelText('Slug'), 'featured')
    const form = getCreateForm()
    await user.click(within(form).getByRole('button', { name: 'Create tag' }))

    await waitFor(() => {
      expect(screen.getByText('Featured')).toBeInTheDocument()
      expect(screen.getByText('featured')).toBeInTheDocument()
    })
  })

  it('shows validation errors for invalid slug', async () => {
    const user = userEvent.setup()
    renderTagsPage()

    await user.type(screen.getByLabelText('Name'), 'Bad Slug')
    await user.type(screen.getByLabelText('Slug'), 'Bad Slug')
    const form = getCreateForm()
    await user.click(within(form).getByRole('button', { name: 'Create tag' }))

    expect(
      await screen.findByText('Use lowercase letters, numbers, and hyphens'),
    ).toBeInTheDocument()
  })

  it('updates a tag name and slug', async () => {
    const user = userEvent.setup()
    renderTagsPage()

    await user.type(screen.getByLabelText('Name'), 'Featured')
    await user.type(screen.getByLabelText('Slug'), 'featured')
    const createForm = getCreateForm()
    await user.click(within(createForm).getByRole('button', { name: 'Create tag' }))

    await waitFor(() => {
      expect(screen.getByText('Featured')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Edit' }))

    const editForm = getEditForm()
    const nameInput = within(editForm).getByLabelText('Name')
    const slugInput = within(editForm).getByLabelText('Slug')

    await user.clear(nameInput)
    await user.type(nameInput, 'Highlight')
    await user.clear(slugInput)
    await user.type(slugInput, 'highlight')
    await user.click(within(editForm).getByRole('button', { name: 'Save changes' }))

    await waitFor(() => {
      expect(screen.getByText('Highlight')).toBeInTheDocument()
      expect(screen.getByText('highlight')).toBeInTheDocument()
    })
  })

  it('deletes a tag after confirmation', async () => {
    const user = userEvent.setup()
    renderTagsPage()

    await user.type(screen.getByLabelText('Name'), 'Draft')
    await user.type(screen.getByLabelText('Slug'), 'draft')
    const form = getCreateForm()
    await user.click(within(form).getByRole('button', { name: 'Create tag' }))

    await waitFor(() => {
      expect(screen.getByText('Draft')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Delete' }))
    const dialog = screen.getByRole('dialog')
    expect(dialog).toBeInTheDocument()

    await user.click(within(dialog).getByRole('button', { name: 'Delete' }))

    await waitFor(() => {
      expect(screen.queryByText('Draft')).not.toBeInTheDocument()
    })
  })
})

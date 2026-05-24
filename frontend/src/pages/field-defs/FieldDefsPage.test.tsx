import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { FieldDefsPage } from '@/pages/field-defs/FieldDefsPage'
import { resetFieldDefStore } from '@tests/msw/handlers/field-def'
import { resetEntityTypeStore, seedEntityTypes } from '@tests/msw/handlers/entity-type'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderFieldDefsPage(entityTypeId = 1) {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/entity-types/${String(entityTypeId)}/fields`]}>
      <Routes>
        <Route path="/entity-types/:entityTypeId/fields" element={<FieldDefsPage />} />
      </Routes>
    </MemoryRouter>,
  )
}

function getAddFieldForm(): HTMLElement {
  const form = screen.getByRole('heading', { name: 'Add field' }).closest('form')
  if (form === null) {
    throw new Error('Add field form not found')
  }
  return form
}

describe('FieldDefsPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    resetEntityTypeStore()
    resetFieldDefStore()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('creates a field definition and lists it', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const user = userEvent.setup()
    renderFieldDefsPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Article' })).toBeInTheDocument()
      expect(screen.getByText('No fields yet')).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText('Field key'), 'title')
    const form = getAddFieldForm()
    await user.click(within(form).getByRole('button', { name: 'Add field' }))

    await waitFor(() => {
      expect(screen.getByText('title')).toBeInTheDocument()
    })
  })

  it('shows validation errors for invalid field key', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const user = userEvent.setup()
    renderFieldDefsPage()

    await user.type(screen.getByLabelText('Field key'), 'Bad Key')
    const form = getAddFieldForm()
    await user.click(within(form).getByRole('button', { name: 'Add field' }))

    expect(
      await screen.findByText('Use lowercase letters, numbers, and underscores'),
    ).toBeInTheDocument()
  })

  it('deletes a field definition after confirmation', async () => {
    seedEntityTypes([{ id: 1, name: 'Article', slug: 'article' }])
    const user = userEvent.setup()
    renderFieldDefsPage()

    await user.type(screen.getByLabelText('Field key'), 'body')
    const form = getAddFieldForm()
    await user.click(within(form).getByRole('button', { name: 'Add field' }))

    await waitFor(() => {
      expect(screen.getByText('body')).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: 'Delete' }))
    const dialog = screen.getByRole('dialog')
    expect(dialog).toBeInTheDocument()

    await user.click(within(dialog).getByRole('button', { name: 'Delete' }))

    await waitFor(() => {
      expect(screen.queryByText('body')).not.toBeInTheDocument()
      expect(screen.getByText('No fields yet')).toBeInTheDocument()
    })
  })
})

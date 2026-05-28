import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { VerifyEmailPage } from '@/pages/verify-email/VerifyEmailPage'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

function renderVerifyPage(search: string) {
  return renderWithProviders(
    <MemoryRouter initialEntries={[`/admin/verify-email${search}`]}>
      <Routes>
        <Route path="/admin/verify-email" element={<VerifyEmailPage />} />
        <Route path="/login" element={<div data-testid="login-page">Login</div>} />
      </Routes>
    </MemoryRouter>,
  )
}

describe('VerifyEmailPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('shows a success message when the token is valid', async () => {
    renderVerifyPage('?token=valid')

    expect(await screen.findByText('Your email address has been changed.')).toBeInTheDocument()
  })

  it('shows an expired message on a 410 response', async () => {
    renderVerifyPage('?token=expired')

    await waitFor(() => {
      expect(screen.getByText(/This verification link has expired/)).toBeInTheDocument()
    })
  })

  it('shows an invalid message on a 422 response', async () => {
    renderVerifyPage('?token=whatever')

    await waitFor(() => {
      expect(screen.getByText('This verification link is invalid.')).toBeInTheDocument()
    })
  })

  it('shows an invalid message when no token is present', () => {
    renderVerifyPage('')

    expect(screen.getByText('This verification link is invalid.')).toBeInTheDocument()
  })
})

import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { AccountPage } from '@/pages/account/AccountPage'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { clearAuthSession, seedAdminSession } from '@tests/helpers/auth-session'

function renderAccountPage() {
  return renderWithProviders(
    <MemoryRouter>
      <AccountPage />
    </MemoryRouter>,
  )
}

describe('AccountPage', () => {
  beforeAll(() => {
    mswServer.listen()
  })

  beforeEach(() => {
    seedAdminSession()
  })

  afterEach(() => {
    mswServer.resetHandlers()
    clearAuthSession()
    cleanup()
  })

  afterAll(() => {
    mswServer.close()
  })

  it('shows the plan, records usage and limits', async () => {
    renderAccountPage()

    expect(await screen.findByText('Current plan: free')).toBeInTheDocument()
    expect(screen.getByText('Records: 12 / 1,000')).toBeInTheDocument()
    // free plan → custom domain blocked
    expect(screen.getByText('Not available on this plan')).toBeInTheDocument()
  })
})

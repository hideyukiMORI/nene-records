import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest'
import { cleanup, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ImportPage } from '@/pages/import/ImportPage'
import { mswServer } from '@tests/msw/server'
import { renderWithProviders } from '@tests/render/render-with-providers'

describe('ImportPage (WXR migration)', () => {
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

  function selectFile() {
    const file = new File(['<rss><channel></channel></rss>'], 'export.xml', {
      type: 'application/xml',
    })
    return userEvent.setup().upload(screen.getByLabelText('WXR file (.xml)'), file)
  }

  it('previews a WXR file, then runs the import', async () => {
    const user = userEvent.setup()
    renderWithProviders(<ImportPage />)

    await selectFile()
    await user.click(screen.getByRole('button', { name: 'Preview' }))

    await waitFor(() => {
      expect(screen.getByText('Import plan (preview)')).toBeInTheDocument()
    })
    // planned + skipped surfaced
    expect(screen.getByText(/Hello World/)).toBeInTheDocument()
    expect(screen.getByText(/image\.jpg/)).toBeInTheDocument()

    // Run import → result card.
    await user.click(screen.getByRole('button', { name: 'Run import' }))
    await waitFor(() => {
      expect(screen.getByText('Import result')).toBeInTheDocument()
    })
    expect(screen.getByText('Import complete.')).toBeInTheDocument()
  })

  it('hides the run-import button until a preview exists', () => {
    renderWithProviders(<ImportPage />)

    expect(screen.getByRole('button', { name: 'Preview' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Run import' })).not.toBeInTheDocument()
  })
})

import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { ImportUrlRedirectsView } from './ImportUrlRedirectsView'

afterEach(cleanup)

describe('ImportUrlRedirectsView', () => {
  it('renders the CSV upload form with the preview button disabled until a file is chosen', () => {
    renderWithProviders(<ImportUrlRedirectsView />)

    expect(screen.getByRole('heading', { name: 'Redirect import (CSV)' })).toBeInTheDocument()
    expect(screen.getByText('CSV file (source,target)')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Preview' })).toBeDisabled()
  })
})

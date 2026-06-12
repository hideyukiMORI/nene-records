import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import type { MediaUsage } from '@/entities/media'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { MediaUsageList } from './MediaUsageList'

function renderList(usages: MediaUsage[], isLoading = false) {
  return renderWithProviders(
    <MemoryRouter>
      <MediaUsageList usages={usages} isLoading={isLoading} />
    </MemoryRouter>,
  )
}

const sampleUsage: MediaUsage = {
  entityId: 7,
  entityTypeSlug: 'post',
  entitySlug: 'hello-world',
  status: 'published',
  fieldKey: 'cover',
  title: 'Hello World',
}

describe('MediaUsageList', () => {
  afterEach(() => {
    cleanup()
  })

  it('renders a blocking message and a link to each referencing entity', () => {
    renderList([sampleUsage])

    expect(screen.getByRole('alert')).toBeInTheDocument()
    const link = screen.getByRole('link', { name: 'Hello World' })
    expect(link).toHaveAttribute('href', '/admin/post/7')
  })

  it('falls back to the entity slug when there is no title', () => {
    renderList([{ ...sampleUsage, title: null }])

    expect(screen.getByRole('link', { name: 'hello-world' })).toBeInTheDocument()
  })

  it('renders nothing when there are no usages', () => {
    const { container } = renderList([])

    expect(container).toBeEmptyDOMElement()
  })

  it('shows a loading indicator while usages are being fetched', () => {
    renderList([], true)

    expect(screen.getByText(/Checking where this file is used/i)).toBeInTheDocument()
  })
})

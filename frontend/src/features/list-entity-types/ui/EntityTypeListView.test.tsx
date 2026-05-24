import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { buildEntityType, buildEntityTypeId } from '@tests/factories/entity-type'
import { EntityTypeListView } from './EntityTypeListView'

describe('EntityTypeListView', () => {
  it('renders empty state when there are no items', () => {
    renderWithProviders(
      <EntityTypeListView
        items={[]}
        isLoading={false}
        isError={false}
        errorTitle={null}
        onRetry={vi.fn()}
      />,
    )

    expect(screen.getByRole('heading', { name: 'No entity types yet' })).toBeInTheDocument()
  })

  it('renders error state with retry action', async () => {
    const user = userEvent.setup()
    const onRetry = vi.fn()

    renderWithProviders(
      <EntityTypeListView
        items={[]}
        isLoading={false}
        isError={true}
        errorTitle="Server error"
        onRetry={onRetry}
      />,
    )

    expect(screen.getByText('Server error')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Retry' }))
    expect(onRetry).toHaveBeenCalledOnce()
  })

  it('renders entity type items', () => {
    renderWithProviders(
      <EntityTypeListView
        items={[
          buildEntityType(),
          buildEntityType({ name: 'Page', slug: 'page', id: buildEntityTypeId(2) }),
        ]}
        isLoading={false}
        isError={false}
        errorTitle={null}
        onRetry={vi.fn()}
      />,
    )

    expect(screen.getByText('Article')).toBeInTheDocument()
    expect(screen.getByText('page')).toBeInTheDocument()
  })
})

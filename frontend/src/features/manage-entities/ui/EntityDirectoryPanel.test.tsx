import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { renderWithI18n } from '@/shared/i18n/test-helpers'
import type { DirectoryRecord } from '../lib/build-permalink-tree'
import { EntityDirectoryPanel } from './EntityDirectoryPanel'

afterEach(cleanup)

const RECORDS: DirectoryRecord[] = [
  { id: 1, permalink: '/company/about', label: 'About Us', status: 'published', updatedAt: null },
  {
    id: 2,
    permalink: '/company/about/team',
    label: 'Our Team',
    status: 'draft',
    updatedAt: '2026-06-20T00:00:00Z',
  },
]

function renderPanel(props?: Partial<Parameters<typeof EntityDirectoryPanel>[0]>) {
  return renderWithI18n(
    <MemoryRouter>
      <EntityDirectoryPanel
        entityTypeSlug="pages"
        records={RECORDS}
        truncated={false}
        isLoading={false}
        isError={false}
        errorTitle={null}
        onRetry={() => {}}
        onCreateHere={() => {}}
        {...props}
      />
    </MemoryRouter>,
  )
}

describe('EntityDirectoryPanel', () => {
  it('shows an empty state with no record links when there are no permalink records', () => {
    renderPanel({ records: [] })
    expect(screen.getByText('No pages with custom paths yet')).toBeInTheDocument()
    expect(screen.queryAllByRole('link')).toHaveLength(0)
  })

  it('renders pure folders and record links derived from the permalink paths', () => {
    renderPanel()

    // `/company` has no page of its own → a pure folder.
    expect(screen.getByText('company/')).toBeInTheDocument()
    // The top-level `About Us` page links to its admin edit page.
    expect(screen.getByRole('link', { name: 'About Us' })).toHaveAttribute('href', '/admin/pages/1')
    // The cumulative path is shown for each visible node.
    expect(screen.getByText('/company/about')).toBeInTheDocument()
  })

  it('initially collapses below the top level, expanding on demand (#657)', async () => {
    const user = userEvent.setup()
    renderPanel()

    // `About Us` (depth 1) starts collapsed → its child `Our Team` is hidden.
    expect(screen.queryByRole('link', { name: 'Our Team' })).not.toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Expand' }))

    expect(screen.getByRole('link', { name: 'Our Team' })).toHaveAttribute('href', '/admin/pages/2')
    expect(screen.getByText('/company/about/team')).toBeInTheDocument()
  })

  it('shows a child count next to folders and section pages (#657)', () => {
    renderPanel()

    // `company` (folder) and `About Us` (a page that also has children) each show (1).
    expect(screen.getAllByText('(1)')).toHaveLength(2)
  })

  it('creates a new page under a folder, passing its permalink prefix (#658)', async () => {
    const user = userEvent.setup()
    const onCreateHere = vi.fn()
    renderPanel({ onCreateHere })

    await user.click(screen.getByRole('button', { name: 'New page under /company' }))

    expect(onCreateHere).toHaveBeenCalledWith('/company/')
  })
})

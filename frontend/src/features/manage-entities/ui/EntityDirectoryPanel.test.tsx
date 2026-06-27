import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, fireEvent, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { renderWithProviders } from '@tests/render/render-with-providers'
import type { DirectoryRecord } from '../lib/build-permalink-tree'
import { clearDirectoryDragPayload, setDirectoryDragPayload } from './directory-dnd'
import { EntityDirectoryPanel } from './EntityDirectoryPanel'

afterEach(() => {
  cleanup()
  clearDirectoryDragPayload()
})

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
  return renderWithProviders(
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

  it('opens a move confirmation when a record is dropped onto a folder (#659)', () => {
    setDirectoryDragPayload({ id: 2, permalink: '/company/about/team', label: 'Our Team' })
    renderPanel()

    const companyRow = screen.getByText('company/').closest('div')
    if (companyRow === null) {
      throw new Error('Expected the /company folder row to render.')
    }
    fireEvent.drop(companyRow)

    // The confirm dialog appears, showing the computed target permalink.
    expect(screen.getByText('Move page?')).toBeInTheDocument()
    expect(screen.getByText(/\/company\/team/)).toBeInTheDocument()
  })

  it('filters the tree as you type, surfacing collapsed matches (#659)', async () => {
    const user = userEvent.setup()
    renderPanel()
    const search = screen.getByPlaceholderText(/Filter by path or title/)

    // `Our Team` sits at depth 2 (normally collapsed); filtering by it forces it open.
    await user.type(search, 'team')
    expect(screen.getByRole('link', { name: 'Our Team' })).toBeInTheDocument()

    // A non-matching query hides everything → the no-matches message.
    await user.clear(search)
    await user.type(search, 'zzz')
    expect(screen.getByText(/No pages match/)).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: 'About Us' })).not.toBeInTheDocument()
  })
})

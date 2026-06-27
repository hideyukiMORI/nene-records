import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { renderWithI18n } from '@/shared/i18n/test-helpers'
import type { DirectoryRecord } from '../lib/build-permalink-tree'
import { EntityDirectoryPanel } from './EntityDirectoryPanel'

afterEach(cleanup)

const RECORDS: DirectoryRecord[] = [
  { id: 1, permalink: '/company/about', label: 'About Us', status: 'published' },
  { id: 2, permalink: '/company/about/team', label: 'Our Team', status: 'draft' },
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
    // Records link to their admin edit page.
    expect(screen.getByRole('link', { name: 'About Us' })).toHaveAttribute('href', '/admin/pages/1')
    expect(screen.getByRole('link', { name: 'Our Team' })).toHaveAttribute('href', '/admin/pages/2')
    // The cumulative path is shown for each node.
    expect(screen.getByText('/company/about/team')).toBeInTheDocument()
  })
})

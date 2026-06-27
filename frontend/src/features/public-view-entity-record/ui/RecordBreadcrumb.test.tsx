import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { renderWithI18n } from '@/shared/i18n/test-helpers'
import type { PublicRecordBreadcrumbDto } from '@/shared/lib/public-record-hierarchy'
import { RecordBreadcrumb } from './RecordBreadcrumb'

afterEach(cleanup)

function renderBreadcrumb(
  items: PublicRecordBreadcrumbDto[],
  options?: Parameters<typeof renderWithI18n>[1],
) {
  return renderWithI18n(
    <MemoryRouter>
      <RecordBreadcrumb items={items} />
    </MemoryRouter>,
    options,
  )
}

describe('RecordBreadcrumb', () => {
  it('renders nothing when there are no crumbs', () => {
    renderBreadcrumb([])
    expect(screen.queryByRole('navigation')).toBeNull()
  })

  it('links published ancestors, plain-texts phantom segments and the current page', () => {
    renderBreadcrumb([
      { label: 'Company', path: null, current: false },
      { label: 'About Us', path: '/company/about', current: false },
      { label: 'Our Team', path: '/company/about/team', current: true },
    ])

    // The Home crumb is always present and links to the site root.
    expect(screen.getByRole('link', { name: 'Home' })).toHaveAttribute('href', '/')
    // A structural segment with no page of its own is text, not a link.
    expect(screen.queryByRole('link', { name: 'Company' })).toBeNull()
    expect(screen.getByText('Company')).toBeInTheDocument()
    // A published ancestor links to its permalink.
    expect(screen.getByRole('link', { name: 'About Us' })).toHaveAttribute('href', '/company/about')
    // The current page is text with aria-current, never a link.
    expect(screen.queryByRole('link', { name: 'Our Team' })).toBeNull()
    expect(screen.getByText('Our Team')).toHaveAttribute('aria-current', 'page')
  })

  it('localizes the Home crumb (ja)', () => {
    renderBreadcrumb([{ label: 'Team', path: '/company/about/team', current: true }], {
      locale: 'ja',
    })
    expect(screen.getByRole('link', { name: 'ホーム' })).toHaveAttribute('href', '/')
  })
})

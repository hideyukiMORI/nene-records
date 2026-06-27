import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { renderWithI18n } from '@/shared/i18n/test-helpers'
import type { PublicRecordChildLinkDto } from '@/shared/lib/public-record-hierarchy'
import { RecordChildPages } from './RecordChildPages'

afterEach(cleanup)

function renderChildPages(items: PublicRecordChildLinkDto[]) {
  return renderWithI18n(
    <MemoryRouter>
      <RecordChildPages items={items} />
    </MemoryRouter>,
  )
}

describe('RecordChildPages', () => {
  it('renders nothing when there are no children', () => {
    renderChildPages([])
    expect(screen.queryByRole('navigation')).toBeNull()
  })

  it('lists each child as a link to its permalink', () => {
    renderChildPages([
      { title: 'History', path: '/company/about/history' },
      { title: 'Team', path: '/company/about/team' },
    ])

    expect(screen.getByRole('heading', { name: 'In this section' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'History' })).toHaveAttribute(
      'href',
      '/company/about/history',
    )
    expect(screen.getByRole('link', { name: 'Team' })).toHaveAttribute(
      'href',
      '/company/about/team',
    )
  })
})

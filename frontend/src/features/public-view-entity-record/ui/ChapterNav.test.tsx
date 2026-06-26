import { afterEach, describe, expect, it } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { renderWithI18n } from '@/shared/i18n/test-helpers'
import { ChapterNav } from './ChapterNav'

afterEach(cleanup)

function renderNav(nav: Parameters<typeof ChapterNav>[0]['nav']) {
  return renderWithI18n(
    <MemoryRouter>
      <ChapterNav nav={nav} />
    </MemoryRouter>,
  )
}

describe('ChapterNav', () => {
  it('renders prev / contents / next links and the position label', () => {
    renderNav({
      indexUrl: '/work/w',
      prevUrl: '/work/w-1',
      nextUrl: '/work/w-3',
      chapterNo: 2,
      chapterTotal: 11,
    })

    expect(screen.getByRole('link', { name: /Previous chapter/ })).toHaveAttribute(
      'href',
      '/work/w-1',
    )
    expect(screen.getByRole('link', { name: 'Contents' })).toHaveAttribute('href', '/work/w')
    expect(screen.getByRole('link', { name: /Next chapter/ })).toHaveAttribute('href', '/work/w-3')
    expect(screen.getByText('Chapter 2 / 11')).toBeInTheDocument()
  })

  it('hides the previous link on the first chapter', () => {
    renderNav({
      indexUrl: '/work/w',
      prevUrl: null,
      nextUrl: '/work/w-2',
      chapterNo: 1,
      chapterTotal: 3,
    })

    expect(screen.queryByRole('link', { name: /Previous chapter/ })).toBeNull()
    expect(screen.getByRole('link', { name: /Next chapter/ })).toHaveAttribute('href', '/work/w-2')
  })

  it('hides the next link on the last chapter', () => {
    renderNav({
      indexUrl: '/work/w',
      prevUrl: '/work/w-2',
      nextUrl: null,
      chapterNo: 3,
      chapterTotal: 3,
    })

    expect(screen.queryByRole('link', { name: /Next chapter/ })).toBeNull()
    expect(screen.getByRole('link', { name: /Previous chapter/ })).toHaveAttribute(
      'href',
      '/work/w-2',
    )
  })

  it('localizes labels (ja)', () => {
    renderWithI18n(
      <MemoryRouter>
        <ChapterNav
          nav={{
            indexUrl: '/work/w',
            prevUrl: '/work/w-1',
            nextUrl: '/work/w-3',
            chapterNo: 2,
            chapterTotal: 11,
          }}
        />
      </MemoryRouter>,
      { locale: 'ja' },
    )

    expect(screen.getByRole('link', { name: /前の章/ })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: '目次' })).toBeInTheDocument()
    expect(screen.getByText('第2章 / 全11章')).toBeInTheDocument()
  })
})

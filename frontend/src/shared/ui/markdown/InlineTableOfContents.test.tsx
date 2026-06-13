import { cleanup, render, screen } from '@testing-library/react'
import { afterEach, describe, expect, it } from 'vitest'
import { I18nProvider } from '@/shared/i18n'
import { InlineTableOfContents } from './InlineTableOfContents'

afterEach(cleanup)

function renderWithI18n(markdown: string, minHeadings?: number) {
  return render(
    <I18nProvider>
      <InlineTableOfContents markdown={markdown} minHeadings={minHeadings} />
    </I18nProvider>,
  )
}

describe('InlineTableOfContents', () => {
  it('renders the TOC when headings meet the threshold', () => {
    const md = ['## One', '## Two', '## Three'].join('\n')
    renderWithI18n(md)

    expect(screen.getByRole('link', { name: 'One' })).toHaveAttribute('href', '#one')
    expect(screen.getByRole('link', { name: 'Three' })).toHaveAttribute('href', '#three')
  })

  it('renders nothing when headings are below the threshold', () => {
    const { container } = renderWithI18n(['## Only', '## Two'].join('\n'))
    expect(container).toBeEmptyDOMElement()
  })

  it('renders nothing when there are no headings', () => {
    const { container } = renderWithI18n('just a paragraph')
    expect(container).toBeEmptyDOMElement()
  })

  it('respects a custom minHeadings', () => {
    renderWithI18n(['## One', '## Two'].join('\n'), 2)
    expect(screen.getByRole('link', { name: 'One' })).toBeInTheDocument()
  })
})

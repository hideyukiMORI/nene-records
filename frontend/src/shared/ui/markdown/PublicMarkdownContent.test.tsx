import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { PublicMarkdownContent } from '@/shared/ui/markdown/PublicMarkdownContent'

describe('PublicMarkdownContent', () => {
  it('renders markdown headings and emphasis', () => {
    render(
      <PublicMarkdownContent
        markdown={`## Hello

**bold** text`}
      />,
    )

    expect(screen.getByRole('heading', { level: 2, name: 'Hello' })).toBeInTheDocument()
    expect(screen.getByText('bold')).toBeInTheDocument()
  })

  it('returns null for empty markdown', () => {
    const { container } = render(<PublicMarkdownContent markdown="   " />)

    expect(container).toBeEmptyDOMElement()
  })
})

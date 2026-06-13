import ReactMarkdown, { type Components } from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { extractHeadings } from '@/shared/lib/markdown-headings'
import { ResponsiveImage } from '@/shared/ui/media/ResponsiveImage'
import './markdown-content.css'

export interface PublicMarkdownContentProps {
  markdown: string
}

export function PublicMarkdownContent({ markdown }: PublicMarkdownContentProps) {
  if (markdown.trim() === '') {
    return null
  }

  // Single source of truth for anchor ids (same list the TOC consumes). Headings
  // render in document order, so a cursor assigns each the matching slug.
  const headings = extractHeadings(markdown)
  let cursor = 0
  const nextHeadingId = (): string | undefined => headings[cursor++]?.slug

  const components: Components = {
    // Serve resized derivatives (srcset) for media-library images in content.
    img: ({ src, alt }) =>
      typeof src === 'string' ? <ResponsiveImage src={src} alt={alt ?? ''} /> : null,
    h1: ({ children }) => <h1 id={nextHeadingId()}>{children}</h1>,
    h2: ({ children }) => <h2 id={nextHeadingId()}>{children}</h2>,
    h3: ({ children }) => <h3 id={nextHeadingId()}>{children}</h3>,
    h4: ({ children }) => <h4 id={nextHeadingId()}>{children}</h4>,
    h5: ({ children }) => <h5 id={nextHeadingId()}>{children}</h5>,
    h6: ({ children }) => <h6 id={nextHeadingId()}>{children}</h6>,
  }

  return (
    <div className="markdown-body">
      <ReactMarkdown
        remarkPlugins={[remarkGfm]}
        disallowedElements={['script', 'style', 'iframe', 'object', 'embed']}
        unwrapDisallowed
        components={components}
      >
        {markdown}
      </ReactMarkdown>
    </div>
  )
}

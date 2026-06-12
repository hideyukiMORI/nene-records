import ReactMarkdown, { type Components } from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { ResponsiveImage } from '@/shared/ui/media/ResponsiveImage'
import './markdown-content.css'

export interface PublicMarkdownContentProps {
  markdown: string
}

// Serve resized derivatives (srcset) for media-library images embedded in content.
const COMPONENTS: Components = {
  img: ({ src, alt }) =>
    typeof src === 'string' ? <ResponsiveImage src={src} alt={alt ?? ''} /> : null,
}

export function PublicMarkdownContent({ markdown }: PublicMarkdownContentProps) {
  if (markdown.trim() === '') {
    return null
  }

  return (
    <div className="markdown-body">
      <ReactMarkdown
        remarkPlugins={[remarkGfm]}
        disallowedElements={['script', 'style', 'iframe', 'object', 'embed']}
        unwrapDisallowed
        components={COMPONENTS}
      >
        {markdown}
      </ReactMarkdown>
    </div>
  )
}

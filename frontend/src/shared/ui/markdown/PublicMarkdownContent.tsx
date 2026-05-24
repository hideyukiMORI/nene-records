import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import './markdown-content.css'

export interface PublicMarkdownContentProps {
  markdown: string
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
      >
        {markdown}
      </ReactMarkdown>
    </div>
  )
}

import { TableOfContents } from '@/shared/ui/markdown'
import { usePageContent } from '../page-content-context'

/**
 * Table-of-contents widget. Reads the current page's markdown from context and
 * renders h2/h3 anchors. Renders nothing when the page has no such headings.
 */
export function TocWidget() {
  const markdown = usePageContent()
  return <TableOfContents markdown={markdown} />
}

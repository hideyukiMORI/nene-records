import { extractHeadings } from '@/shared/lib/markdown-headings'
import { useTranslation } from '@/shared/i18n'
import { Card, Text } from '@/shared/ui'
import { TableOfContents } from './TableOfContents'

export interface InlineTableOfContentsProps {
  /** Markdown whose headings build the list. */
  markdown: string
  /** Shallowest heading level to include (inclusive). Default 2. */
  minDepth?: number
  /** Deepest heading level to include (inclusive). Default 3. */
  maxDepth?: number
  /** Minimum number of in-range headings required to render. Default 3. */
  minHeadings?: number
}

/**
 * Inline, titled table of contents for the top of an article body. Renders
 * nothing unless the markdown has at least `minHeadings` headings in range, so
 * short posts are left alone. Anchors reuse PublicMarkdownContent's slugs.
 */
export function InlineTableOfContents({
  markdown,
  minDepth = 2,
  maxDepth = 3,
  minHeadings = 3,
}: InlineTableOfContentsProps) {
  const { t } = useTranslation()
  const headingCount = extractHeadings(markdown).filter(
    (heading) => heading.depth >= minDepth && heading.depth <= maxDepth,
  ).length

  if (headingCount < minHeadings) {
    return null
  }

  return (
    <Card as="section" aria-label={t('public.toc.title')}>
      <Text as="h2" variant="heading-sm">
        {t('public.toc.title')}
      </Text>
      <TableOfContents markdown={markdown} minDepth={minDepth} maxDepth={maxDepth} />
    </Card>
  )
}

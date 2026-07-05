import { useTranslation } from '@/shared/i18n'
import { extractHeadings, type MarkdownHeading } from '@/shared/lib/markdown-headings'

export interface TableOfContentsProps {
  /** Markdown whose headings build the list. */
  markdown: string
  /** Shallowest heading level to include (inclusive). Default 2. */
  minDepth?: number
  /** Deepest heading level to include (inclusive). Default 3. */
  maxDepth?: number
}

/**
 * Renders an in-page table of contents from a markdown string's headings.
 * Anchors point at the ids assigned by PublicMarkdownContent (same slugger),
 * so both must derive from the same markdown. Renders nothing when empty.
 */
export function TableOfContents({ markdown, minDepth = 2, maxDepth = 3 }: TableOfContentsProps) {
  const { t } = useTranslation()
  const headings = extractHeadings(markdown).filter(
    (heading) => heading.depth >= minDepth && heading.depth <= maxDepth,
  )

  if (headings.length === 0) {
    return null
  }

  return (
    <nav aria-label={t('common.tableOfContents')}>
      <ul className="flex flex-col gap-stack-xs">
        {headings.map((heading: MarkdownHeading, index) => (
          <li
            key={`${heading.slug}-${String(index)}`}
            style={{ paddingInlineStart: `${String((heading.depth - minDepth) * 12)}px` }}
          >
            <a
              href={`#${heading.slug}`}
              className="text-body text-accent underline hover:no-underline"
            >
              {heading.text}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  )
}

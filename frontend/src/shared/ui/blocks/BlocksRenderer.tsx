import { parseBlocksDocument, type CalloutKind } from '@/shared/lib/blocks-document'
import { PublicMarkdownContent } from '@/shared/ui/markdown'

export interface BlocksRendererProps {
  /** The stored `blocks` field value — a JSON-string blocks document. */
  documentJson: string
}

/**
 * Renders a typed-block document (#486) on the public site. First-party only —
 * markdown flows through the sanitizing {@link PublicMarkdownContent} (no raw
 * HTML). Output is `.nene-public`-scoped (styled by public-site.css) and theme
 * tokens drive all colors, so blocks track the active public theme + light/dark.
 * Unknown/empty blocks are skipped (the server already validated on write).
 */
export function BlocksRenderer({ documentJson }: BlocksRendererProps) {
  const blocks = parseBlocksDocument(documentJson)
  if (blocks.length === 0) {
    return null
  }

  return (
    <div className="blocks">
      {blocks.map((block) => {
        if (block.type === 'text') {
          if (block.data.markdown.trim() === '') {
            return null
          }
          return (
            <div key={block.id} className="block block--text">
              <PublicMarkdownContent markdown={block.data.markdown} />
            </div>
          )
        }

        if (block.data.body.trim() === '') {
          return null
        }
        const title = block.data.title
        return (
          <aside key={block.id} className="callout" data-callout-kind={block.data.kind} role="note">
            <div className="callout__body">
              <span className="sr-only">{CALLOUT_SR_PREFIX[block.data.kind]}</span>
              {title !== undefined && title.trim() !== '' ? (
                <p className="callout__title">{title}</p>
              ) : null}
              <PublicMarkdownContent markdown={block.data.body} />
            </div>
          </aside>
        )
      })}
    </div>
  )
}

/**
 * Screen-reader-only kind prefix (C4 SEO/a11y projection). Japanese to match the
 * current single-locale consumer site; consumer i18n is a separate effort.
 */
const CALLOUT_SR_PREFIX: Record<CalloutKind, string> = {
  info: '情報: ',
  warn: '警告: ',
  ok: '成功: ',
  danger: 'エラー: ',
}

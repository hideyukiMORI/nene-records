import {
  isSafeHref,
  parseBlocksDocument,
  type Block,
  type CalloutKind,
  type HeroBlockData,
} from '@/shared/lib/blocks-document'
import { PublicMarkdownContent } from '@/shared/ui/markdown'
import { ResponsiveImage } from '@/shared/ui/media/ResponsiveImage'

export interface BlocksRendererProps {
  /** The stored `blocks` field value — a JSON-string blocks document. */
  documentJson: string
}

/**
 * Renders a typed-block document (#486) on the public site. First-party only —
 * markdown flows through the sanitizing {@link PublicMarkdownContent} (no raw
 * HTML), CTA hrefs are allowlisted. Output is `.nene-public`-scoped (styled by
 * public-site.css) and theme tokens drive all colors, so blocks track the active
 * public theme + light/dark. Unknown/empty blocks are skipped.
 */
export function BlocksRenderer({ documentJson }: BlocksRendererProps) {
  const blocks = parseBlocksDocument(documentJson)
  if (blocks.length === 0) {
    return null
  }

  return (
    <div className="blocks">
      {blocks.map((block) => (
        <ConsumerBlock key={block.id} block={block} />
      ))}
    </div>
  )
}

function ConsumerBlock({ block }: { block: Block }) {
  switch (block.type) {
    case 'text':
      if (block.data.markdown.trim() === '') {
        return null
      }
      return (
        <div className="block block--text">
          <PublicMarkdownContent markdown={block.data.markdown} />
        </div>
      )
    case 'callout': {
      if (block.data.body.trim() === '') {
        return null
      }
      const title = block.data.title
      return (
        <aside className="callout" data-callout-kind={block.data.kind} role="note">
          <div className="callout__body">
            <span className="sr-only">{CALLOUT_SR_PREFIX[block.data.kind]}</span>
            {title !== undefined && title.trim() !== '' ? (
              <p className="callout__title">{title}</p>
            ) : null}
            <PublicMarkdownContent markdown={block.data.body} />
          </div>
        </aside>
      )
    }
    case 'hero':
      return <ConsumerHero data={block.data} />
  }
}

/**
 * Hero block (#486 S2–S3): reuses existing `.hero__*` presentation; variant →
 * data-hero (C9). Art image (S3) shows in a two-column grid for standard /
 * fullbleed; the `minimal` variant is copy-only.
 */
function ConsumerHero({ data }: { data: HeroBlockData }) {
  if (data.heading.trim() === '') {
    return null
  }
  const lead = data.lead?.trim()
  const primary = ctaProps(data.ctaLabel, data.ctaUrl)
  const ghost = ctaProps(data.ghostLabel, data.ghostUrl)
  const media = data.media

  const copy = (
    <div className="hero__copy">
      {data.kicker !== undefined && data.kicker.trim() !== '' ? (
        <p className="eyebrow hero__kicker">{data.kicker}</p>
      ) : null}
      <h2 className="hero__title">{renderEmphasis(data.heading)}</h2>
      {lead !== undefined && lead !== '' ? <p className="hero__lead">{lead}</p> : null}
      {primary !== null || ghost !== null ? (
        <div className="hero__cta">
          {primary !== null ? (
            <a className="btn btn--primary" href={primary.href}>
              {primary.label}
            </a>
          ) : null}
          {ghost !== null ? (
            <a className="btn btn--ghost" href={ghost.href}>
              {ghost.label}
            </a>
          ) : null}
        </div>
      ) : null}
    </div>
  )

  return (
    <section className="hero--block" data-hero={data.variant}>
      {media !== undefined && data.variant !== 'minimal' ? (
        <div className="hero__grid">
          {copy}
          <div className="hero__art">
            <div className="hero__art-frame">
              <ResponsiveImage
                src={media.url}
                alt={media.alt ?? ''}
                sizes="(max-width: 768px) 100vw, 480px"
              />
            </div>
          </div>
        </div>
      ) : (
        copy
      )}
    </section>
  )
}

function ctaProps(
  label: string | undefined,
  url: string | undefined,
): { label: string; href: string } | null {
  if (label === undefined || label.trim() === '' || url === undefined || !isSafeHref(url)) {
    return null
  }
  return { label, href: url }
}

/** Render `*emphasis*` markers as accent `<em>`. Text-only (no HTML injection). */
function renderEmphasis(text: string) {
  return text
    .split('*')
    .map((part, index) => (index % 2 === 1 ? <em key={index}>{part}</em> : part))
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

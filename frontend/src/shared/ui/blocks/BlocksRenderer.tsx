import {
  isSafeHref,
  parseBlocksDocument,
  type Block,
  type CalloutKind,
  type ChartBlockData,
  type ColumnsBlockData,
  type GalleryBlockData,
  type GroupBlockData,
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
    case 'gallery':
      return <ConsumerGallery data={block.data} />
    case 'chart':
      return <ConsumerChart data={block.data} />
    case 'group':
      return <ConsumerGroup data={block.data} />
    case 'columns':
      return <ConsumerColumns data={block.data} />
  }
}

/**
 * Columns block (#491 WS2): a responsive multi-column layout (2-4 columns of leaf
 * blocks). Stacks to one column on narrow viewports (see `.columns` in
 * public-site.css). Children are leaf blocks only (depth 2).
 */
function ConsumerColumns({ data }: { data: ColumnsBlockData }) {
  if (data.columns.length === 0) {
    return null
  }
  return (
    <div className="columns" data-columns={data.columns.length}>
      {data.columns.map((column, index) => (
        <div key={index} className="columns__col">
          {column.children.map((child) => (
            <ConsumerBlock key={child.id} block={child} />
          ))}
        </div>
      ))}
    </div>
  )
}

/**
 * Group block (#491 WS2): a layout container that renders its leaf children with
 * a tone (plain / muted band / bordered card). Children are leaf blocks only
 * (depth 2), so this never recurses into another group.
 */
function ConsumerGroup({ data }: { data: GroupBlockData }) {
  if (data.children.length === 0) {
    return null
  }
  return (
    <div className="group" data-group-tone={data.tone}>
      {data.children.map((child) => (
        <ConsumerBlock key={child.id} block={child} />
      ))}
    </div>
  )
}

/**
 * Chart block (#486 S5): a first-party minimal bar/line SVG (C2 — no charting
 * library, no JS) with the data also projected as an sr-only summary + table (C4).
 */
function ConsumerChart({ data }: { data: ChartBlockData }) {
  const series = data.series
  if (series.length < 2) {
    return null
  }

  const width = 640
  const height = 240
  const padLeft = 46
  const padRight = 10
  const padTop = 24
  const padBottom = 28
  const innerWidth = width - padLeft - padRight
  const innerHeight = height - padTop - padBottom
  const count = series.length
  const max = Math.max(1, ...series.map((point) => point.value))
  const slot = innerWidth / count
  const center = (index: number) => padLeft + slot * index + slot / 2
  const yOf = (value: number) => padTop + innerHeight * (1 - value / max)
  const barWidth = slot * 0.6
  const isBar = data.chartType === 'bar'
  // Keep line points / dots / value labels on-canvas even for negative values
  // (bars already collapse to height 0 via Math.max below).
  const clampY = (value: number) => Math.min(padTop + innerHeight, Math.max(padTop, yOf(value)))
  const linePoints = series
    .map((point, index) => `${center(index).toFixed(1)},${clampY(point.value).toFixed(1)}`)
    .join(' ')
  const ticks = [0, 0.25, 0.5, 0.75, 1]
  const formatTick = (value: number) =>
    Number.isInteger(value) ? String(value) : String(Number(value.toFixed(2)))
  const title = data.title

  return (
    <figure className="chart" data-chart-type={data.chartType}>
      {title !== undefined && title.trim() !== '' ? (
        <figcaption className="chart__title">{title}</figcaption>
      ) : null}
      <div className="chart__plot">
        <svg
          viewBox={`0 0 ${String(width)} ${String(height)}`}
          aria-hidden="true"
          className="chart__svg"
        >
          {ticks.map((fraction) => {
            const gridY = padTop + innerHeight * (1 - fraction)
            return (
              <g key={fraction}>
                <line
                  x1={padLeft}
                  x2={width - padRight}
                  y1={gridY}
                  y2={gridY}
                  className={fraction === 0 ? 'chart__axis' : 'chart__grid'}
                />
                <text x={padLeft - 8} y={gridY + 4} textAnchor="end" className="chart__ytick">
                  {formatTick(max * fraction)}
                </text>
              </g>
            )
          })}
          {isBar
            ? series.map((point, index) => (
                <rect
                  key={index}
                  x={padLeft + slot * index + (slot - barWidth) / 2}
                  y={yOf(point.value)}
                  width={barWidth}
                  height={Math.max(0, padTop + innerHeight - yOf(point.value))}
                  rx="3"
                  className="chart__bar"
                />
              ))
            : null}
          {isBar ? null : <polyline points={linePoints} fill="none" className="chart__line" />}
          {isBar
            ? null
            : series.map((point, index) => (
                <circle
                  key={index}
                  cx={center(index)}
                  cy={clampY(point.value)}
                  r="3.5"
                  className="chart__dot"
                />
              ))}
          {series.map((point, index) => (
            <text
              key={index}
              x={center(index)}
              y={clampY(point.value) - 7}
              textAnchor="middle"
              className="chart__val"
            >
              {point.value}
            </text>
          ))}
        </svg>
        <div className="chart__labels" aria-hidden="true">
          {series.map((point, index) => (
            <span key={index}>{point.label}</span>
          ))}
        </div>
      </div>
      <span className="sr-only">{data.summary}</span>
      <table className="chart__table sr-only">
        <caption>{title ?? ''}</caption>
        <thead>
          <tr>
            <th scope="col">ラベル</th>
            <th scope="col">値</th>
          </tr>
        </thead>
        <tbody>
          {series.map((point, index) => (
            <tr key={index}>
              <th scope="row">{point.label}</th>
              <td>{point.value}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </figure>
  )
}

/**
 * Gallery block (#486 S4): a no-JS scroll-snap carousel or a CSS grid of library
 * images. Each slide's alt is the C4 a11y/SEO projection (the <img alt>).
 */
function ConsumerGallery({ data }: { data: GalleryBlockData }) {
  if (data.items.length === 0) {
    return null
  }
  return (
    <section className={`gallery gallery--${data.layout}`}>
      <ul className="gallery__track">
        {data.items.map((item, index) => (
          <li className="gallery__slide" key={`${item.mediaId}-${String(index)}`}>
            <ResponsiveImage
              src={item.url}
              alt={item.alt}
              sizes="(max-width: 768px) 90vw, 360px"
              className="gallery__media"
            />
            {item.caption !== undefined && item.caption.trim() !== '' ? (
              <p className="gallery__cap">{item.caption}</p>
            ) : null}
          </li>
        ))}
      </ul>
    </section>
  )
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

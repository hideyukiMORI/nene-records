/**
 * Typed post-block document model (epic #486 S1b–S4).
 *
 * A `blocks` field value is an ordered list of curated, typed blocks
 * (`[{ id, type, data }]`) serialized to a JSON string. The trust boundary is the
 * server (`src/BlocksField/BlocksDocumentValidator.php`); this module mirrors that
 * contract for the admin editor (UX validation) and the consumer renderer
 * (second line of defense). Keep the limits and shapes in sync with
 * `docs/blocks/blocks.schema.json`.
 *
 * Pure TS — no React, i18n, or DOM — so every layer (entities, features,
 * shared/ui) can import it.
 */

const BLOCK_TYPES = ['text', 'callout', 'hero', 'gallery', 'chart', 'group', 'columns'] as const
export type BlockType = (typeof BLOCK_TYPES)[number]

export const CALLOUT_KINDS = ['info', 'warn', 'ok', 'danger'] as const
export type CalloutKind = (typeof CALLOUT_KINDS)[number]

export const HERO_VARIANTS = ['standard', 'minimal', 'fullbleed'] as const
export type HeroVariant = (typeof HERO_VARIANTS)[number]

export const GALLERY_LAYOUTS = ['carousel', 'grid'] as const
export type GalleryLayout = (typeof GALLERY_LAYOUTS)[number]

export const CHART_TYPES = ['bar', 'line'] as const
export type ChartType = (typeof CHART_TYPES)[number]

export const GROUP_TONES = ['plain', 'muted', 'card'] as const
export type GroupTone = (typeof GROUP_TONES)[number]

export interface TextBlockData {
  markdown: string
}

export interface CalloutBlockData {
  kind: CalloutKind
  body: string
  title?: string
}

/**
 * A picker-selected library image. `url` is a site-relative `/media/...` path
 * rendered directly on the consumer (the media metadata API is admin-only, so we
 * store the url like the `image` field type); `mediaId` is kept for provenance.
 */
export interface HeroMedia {
  mediaId: string
  url: string
  alt?: string
}

export interface HeroBlockData {
  variant: HeroVariant
  heading: string
  kicker?: string
  lead?: string
  ctaLabel?: string
  ctaUrl?: string
  ghostLabel?: string
  ghostUrl?: string
  media?: HeroMedia
}

/** A gallery slide: a library image (url like HeroMedia) + required alt (C4) + caption. */
export interface GalleryItem {
  mediaId: string
  url: string
  alt: string
  caption?: string
}

export interface GalleryBlockData {
  layout: GalleryLayout
  items: GalleryItem[]
}

/** A chart data point: a label and a numeric value. */
export interface SeriesPoint {
  label: string
  value: number
}

export interface ChartBlockData {
  chartType: ChartType
  title?: string
  series: SeriesPoint[]
  summary: string
}

/** Leaf (non-container) blocks. A group's children are leaf blocks only (depth 2). */
export type LeafBlock =
  | { id: string; type: 'text'; data: TextBlockData }
  | { id: string; type: 'callout'; data: CalloutBlockData }
  | { id: string; type: 'hero'; data: HeroBlockData }
  | { id: string; type: 'gallery'; data: GalleryBlockData }
  | { id: string; type: 'chart'; data: ChartBlockData }

/** Layout container holding leaf child blocks (#491 WS2); not nestable in another container. */
export interface GroupBlockData {
  tone: GroupTone
  children: LeafBlock[]
}

/** One column of a columns block; holds leaf children. */
export interface ColumnsColumn {
  children: LeafBlock[]
}

/** Multi-column layout container (#491 WS2); 2-4 columns of leaf blocks. */
export interface ColumnsBlockData {
  columns: ColumnsColumn[]
}

export type Block =
  | LeafBlock
  | { id: string; type: 'group'; data: GroupBlockData }
  | { id: string; type: 'columns'; data: ColumnsBlockData }

export type BlockValidationCode =
  | 'markdown-required'
  | 'body-required'
  | 'kind-invalid'
  | 'heading-required'
  | 'items-required'
  | 'alt-required'
  | 'series-required'
  | 'series-label-required'
  | 'summary-required'
  | 'children-required'

function isBlockType(value: string): value is BlockType {
  return (BLOCK_TYPES as readonly string[]).includes(value)
}

function isCalloutKind(value: unknown): value is CalloutKind {
  return typeof value === 'string' && (CALLOUT_KINDS as readonly string[]).includes(value)
}

function isHeroVariant(value: unknown): value is HeroVariant {
  return typeof value === 'string' && (HERO_VARIANTS as readonly string[]).includes(value)
}

function isGroupTone(value: unknown): value is GroupTone {
  return typeof value === 'string' && (GROUP_TONES as readonly string[]).includes(value)
}

/** True for non-container blocks (a container's children are leaf-only; depth 2). */
function isLeafBlock(block: Block): block is LeafBlock {
  return block.type !== 'group' && block.type !== 'columns'
}

function isGalleryLayout(value: unknown): value is GalleryLayout {
  return typeof value === 'string' && (GALLERY_LAYOUTS as readonly string[]).includes(value)
}

function isChartType(value: unknown): value is ChartType {
  return typeof value === 'string' && (CHART_TYPES as readonly string[]).includes(value)
}

function optionalString(record: Record<string, unknown>, key: string): string | undefined {
  return typeof record[key] === 'string' ? record[key] : undefined
}

function coerceMedia(raw: unknown): HeroMedia | undefined {
  if (typeof raw !== 'object' || raw === null) {
    return undefined
  }
  const record = raw as Record<string, unknown>
  if (typeof record.url !== 'string' || !isSafeMediaUrl(record.url)) {
    return undefined
  }
  const alt = typeof record.alt === 'string' ? record.alt : undefined
  return {
    mediaId: typeof record.mediaId === 'string' ? record.mediaId : '',
    url: record.url,
    ...(alt !== undefined && alt !== '' ? { alt } : {}),
  }
}

function coerceGalleryItems(raw: unknown): GalleryItem[] {
  if (!Array.isArray(raw)) {
    return []
  }
  const items: GalleryItem[] = []
  for (const entry of raw) {
    if (typeof entry !== 'object' || entry === null) {
      continue
    }
    const record = entry as Record<string, unknown>
    if (typeof record.url !== 'string' || !isSafeMediaUrl(record.url)) {
      continue
    }
    const caption = typeof record.caption === 'string' ? record.caption : undefined
    items.push({
      mediaId: typeof record.mediaId === 'string' ? record.mediaId : '',
      url: record.url,
      alt: typeof record.alt === 'string' ? record.alt : '',
      ...(caption !== undefined && caption !== '' ? { caption } : {}),
    })
  }
  return items
}

function coerceSeries(raw: unknown): SeriesPoint[] {
  if (!Array.isArray(raw)) {
    return []
  }
  const points: SeriesPoint[] = []
  for (const entry of raw) {
    if (typeof entry !== 'object' || entry === null) {
      continue
    }
    const record = entry as Record<string, unknown>
    if (typeof record.value !== 'number' || !Number.isFinite(record.value)) {
      continue
    }
    points.push({
      label: typeof record.label === 'string' ? record.label : '',
      value: record.value,
    })
  }
  return points
}

/** Client-generated stable key (React key / future anchor); opaque, <= 64 chars. */
function newBlockId(): string {
  return globalThis.crypto.randomUUID()
}

export function createBlock(type: BlockType): Block {
  switch (type) {
    case 'text':
      return { id: newBlockId(), type, data: { markdown: '' } }
    case 'callout':
      return { id: newBlockId(), type, data: { kind: 'info', body: '', title: '' } }
    case 'hero':
      return {
        id: newBlockId(),
        type,
        data: {
          variant: 'standard',
          heading: '',
          kicker: '',
          lead: '',
          ctaLabel: '',
          ctaUrl: '',
          ghostLabel: '',
          ghostUrl: '',
        },
      }
    case 'gallery':
      return { id: newBlockId(), type, data: { layout: 'carousel', items: [] } }
    case 'chart':
      return {
        id: newBlockId(),
        type,
        data: { chartType: 'bar', title: '', series: [], summary: '' },
      }
    case 'group':
      return { id: newBlockId(), type, data: { tone: 'plain', children: [] } }
    case 'columns':
      return { id: newBlockId(), type, data: { columns: [{ children: [] }, { children: [] }] } }
  }
}

/**
 * Parse a stored blocks document. Lenient by design: the server already
 * validated on write, so unknown/malformed blocks are dropped rather than
 * throwing, keeping the consumer forward-compatible as the type whitelist grows.
 */
export function parseBlocksDocument(json: string): Block[] {
  if (json.trim() === '') {
    return []
  }

  let decoded: unknown
  try {
    decoded = JSON.parse(json)
  } catch {
    return []
  }

  if (!Array.isArray(decoded)) {
    return []
  }

  const blocks: Block[] = []
  for (const [index, raw] of decoded.entries()) {
    const block = coerceBlock(raw, index, true)
    if (block !== null) {
      blocks.push(block)
    }
  }
  return blocks
}

function coerceBlock(raw: unknown, index: number, allowContainers: boolean): Block | null {
  if (typeof raw !== 'object' || raw === null) {
    return null
  }

  const candidate = raw as Record<string, unknown>
  const type = candidate.type
  if (typeof type !== 'string' || !isBlockType(type)) {
    return null
  }

  // Container blocks are not nestable inside another container (depth 2).
  if (!allowContainers && (type === 'group' || type === 'columns')) {
    return null
  }

  const id =
    typeof candidate.id === 'string' && candidate.id !== '' ? candidate.id : `b${String(index)}`
  const data = typeof candidate.data === 'object' && candidate.data !== null ? candidate.data : {}
  const record = data as Record<string, unknown>

  switch (type) {
    case 'text':
      return {
        id,
        type,
        data: { markdown: typeof record.markdown === 'string' ? record.markdown : '' },
      }
    case 'callout': {
      const title = optionalString(record, 'title')
      return {
        id,
        type,
        data: {
          kind: isCalloutKind(record.kind) ? record.kind : 'info',
          body: typeof record.body === 'string' ? record.body : '',
          ...(title !== undefined && title !== '' ? { title } : {}),
        },
      }
    }
    case 'hero': {
      const media = coerceMedia(record.media)
      return {
        id,
        type,
        data: {
          variant: isHeroVariant(record.variant) ? record.variant : 'standard',
          heading: typeof record.heading === 'string' ? record.heading : '',
          ...defined({
            kicker: optionalString(record, 'kicker'),
            lead: optionalString(record, 'lead'),
            ctaLabel: optionalString(record, 'ctaLabel'),
            ctaUrl: optionalString(record, 'ctaUrl'),
            ghostLabel: optionalString(record, 'ghostLabel'),
            ghostUrl: optionalString(record, 'ghostUrl'),
          }),
          ...(media !== undefined ? { media } : {}),
        },
      }
    }
    case 'gallery':
      return {
        id,
        type,
        data: {
          layout: isGalleryLayout(record.layout) ? record.layout : 'carousel',
          items: coerceGalleryItems(record.items),
        },
      }
    case 'chart':
      return {
        id,
        type,
        data: {
          chartType: isChartType(record.chartType) ? record.chartType : 'bar',
          ...defined({ title: optionalString(record, 'title') }),
          series: coerceSeries(record.series),
          summary: typeof record.summary === 'string' ? record.summary : '',
        },
      }
    case 'group': {
      const rawChildren = Array.isArray(record.children) ? record.children : []
      const children = rawChildren
        .map((child, childIndex) => coerceBlock(child, childIndex, false))
        .filter((child): child is LeafBlock => child !== null && isLeafBlock(child))
      return {
        id,
        type,
        data: { tone: isGroupTone(record.tone) ? record.tone : 'plain', children },
      }
    }
    case 'columns': {
      const rawColumns = Array.isArray(record.columns) ? record.columns : []
      const columns = rawColumns.map((col) => {
        const colRecord =
          typeof col === 'object' && col !== null ? (col as Record<string, unknown>) : {}
        const rawChildren = Array.isArray(colRecord.children) ? colRecord.children : []
        return {
          children: rawChildren
            .map((child, childIndex) => coerceBlock(child, childIndex, false))
            .filter((child): child is LeafBlock => child !== null && isLeafBlock(child)),
        }
      })
      return { id, type, data: { columns } }
    }
  }
}

/** Serialize the editor's blocks to the stored JSON string, dropping empty optionals. */
export function serializeBlocksDocument(blocks: Block[]): string {
  return JSON.stringify(blocks.map(normalizeBlock))
}

function normalizeBlock(block: Block): Block {
  if (block.type === 'callout') {
    const { kind, body, title } = block.data
    return {
      id: block.id,
      type: 'callout',
      data: { kind, body, ...optional({ title }) },
    }
  }
  if (block.type === 'hero') {
    const { variant, heading } = block.data
    return {
      id: block.id,
      type: 'hero',
      data: {
        variant,
        heading,
        ...optional({
          kicker: block.data.kicker,
          lead: block.data.lead,
          ctaLabel: block.data.ctaLabel,
          ctaUrl: block.data.ctaUrl,
          ghostLabel: block.data.ghostLabel,
          ghostUrl: block.data.ghostUrl,
        }),
        ...(block.data.media !== undefined ? { media: block.data.media } : {}),
      },
    }
  }
  if (block.type === 'gallery') {
    return {
      id: block.id,
      type: 'gallery',
      data: {
        layout: block.data.layout,
        items: block.data.items.map((item) => ({
          mediaId: item.mediaId,
          url: item.url,
          alt: item.alt,
          ...(item.caption !== undefined && item.caption.trim() !== ''
            ? { caption: item.caption }
            : {}),
        })),
      },
    }
  }
  if (block.type === 'chart') {
    return {
      id: block.id,
      type: 'chart',
      data: {
        chartType: block.data.chartType,
        ...optional({ title: block.data.title }),
        series: block.data.series,
        summary: block.data.summary,
      },
    }
  }
  if (block.type === 'group') {
    return {
      id: block.id,
      type: 'group',
      data: {
        tone: block.data.tone,
        children: block.data.children.map(normalizeBlock) as LeafBlock[],
      },
    }
  }
  if (block.type === 'columns') {
    return {
      id: block.id,
      type: 'columns',
      data: {
        columns: block.data.columns.map((col) => ({
          children: col.children.map(normalizeBlock) as LeafBlock[],
        })),
      },
    }
  }
  return block
}

/** Keep only fields whose value is defined, preserving empty strings. */
function defined(fields: Record<string, string | undefined>): Record<string, string> {
  const out: Record<string, string> = {}
  for (const [key, value] of Object.entries(fields)) {
    if (value !== undefined) {
      out[key] = value
    }
  }
  return out
}

/** Keep only string fields that are non-empty after trimming. */
function optional(fields: Record<string, string | undefined>): Record<string, string> {
  const out: Record<string, string> = {}
  for (const [key, value] of Object.entries(fields)) {
    if (value !== undefined && value.trim() !== '') {
      out[key] = value
    }
  }
  return out
}

/** UX-only per-block validation; the server remains the trust boundary. */
export function validateBlock(block: Block): BlockValidationCode | null {
  switch (block.type) {
    case 'text':
      return block.data.markdown.trim() === '' ? 'markdown-required' : null
    case 'callout':
      if (!isCalloutKind(block.data.kind)) {
        return 'kind-invalid'
      }
      return block.data.body.trim() === '' ? 'body-required' : null
    case 'hero':
      return block.data.heading.trim() === '' ? 'heading-required' : null
    case 'gallery':
      if (block.data.items.length === 0) {
        return 'items-required'
      }
      return block.data.items.some((item) => item.alt.trim() === '') ? 'alt-required' : null
    case 'chart':
      if (block.data.series.length < 2) {
        return 'series-required'
      }
      if (block.data.series.some((point) => point.label.trim() === '')) {
        return 'series-label-required'
      }
      return block.data.summary.trim() === '' ? 'summary-required' : null
    case 'group':
      return block.data.children.length === 0 ? 'children-required' : null
    case 'columns':
      return block.data.columns.every((col) => col.children.length === 0)
        ? 'children-required'
        : null
  }
}

/**
 * Allowlist safe link targets for rendering as an href; blocks `javascript:` /
 * `data:` and other schemes that could execute script. Mirrors the server's
 * `isSafeUrl`, but treats empty as unsafe (the consumer simply omits the link).
 */
export function isSafeHref(url: string): boolean {
  const trimmed = url.trim()
  // Reject empty and protocol-relative `//host` / backslash-authority `/\host`
  // (browser resolves those cross-origin → open redirect).
  if (trimmed === '' || /^[/\\]{2}/.test(trimmed)) {
    return false
  }
  return /^(https?:\/\/|mailto:|\/|#)/i.test(trimmed)
}

/**
 * Library image url: a same-origin relative `/...` path (local driver) or an
 * `https://` absolute url (object-storage / CDN driver). Mirrors the server's
 * `isSafeMediaUrl`; rejects protocol-relative and insecure schemes.
 */
export function isSafeMediaUrl(url: string): boolean {
  const trimmed = url.trim()
  if (trimmed === '' || /^[/\\]{2}/.test(trimmed)) {
    return false
  }
  return trimmed.startsWith('https://') || trimmed.startsWith('/')
}

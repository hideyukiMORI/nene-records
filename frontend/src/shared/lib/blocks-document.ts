/**
 * Typed post-block document model (epic #486 S1b–S2).
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

const BLOCK_TYPES = ['text', 'callout', 'hero'] as const
export type BlockType = (typeof BLOCK_TYPES)[number]

export const CALLOUT_KINDS = ['info', 'warn', 'ok', 'danger'] as const
export type CalloutKind = (typeof CALLOUT_KINDS)[number]

export const HERO_VARIANTS = ['standard', 'minimal', 'fullbleed'] as const
export type HeroVariant = (typeof HERO_VARIANTS)[number]

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

export type Block =
  | { id: string; type: 'text'; data: TextBlockData }
  | { id: string; type: 'callout'; data: CalloutBlockData }
  | { id: string; type: 'hero'; data: HeroBlockData }

export type BlockValidationCode =
  | 'markdown-required'
  | 'body-required'
  | 'kind-invalid'
  | 'heading-required'

function isBlockType(value: string): value is BlockType {
  return (BLOCK_TYPES as readonly string[]).includes(value)
}

function isCalloutKind(value: unknown): value is CalloutKind {
  return typeof value === 'string' && (CALLOUT_KINDS as readonly string[]).includes(value)
}

function isHeroVariant(value: unknown): value is HeroVariant {
  return typeof value === 'string' && (HERO_VARIANTS as readonly string[]).includes(value)
}

function optionalString(record: Record<string, unknown>, key: string): string | undefined {
  return typeof record[key] === 'string' ? record[key] : undefined
}

function coerceMedia(raw: unknown): HeroMedia | undefined {
  if (typeof raw !== 'object' || raw === null) {
    return undefined
  }
  const record = raw as Record<string, unknown>
  if (typeof record.url !== 'string' || record.url === '') {
    return undefined
  }
  const alt = typeof record.alt === 'string' ? record.alt : undefined
  return {
    mediaId: typeof record.mediaId === 'string' ? record.mediaId : '',
    url: record.url,
    ...(alt !== undefined && alt !== '' ? { alt } : {}),
  }
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
    const block = coerceBlock(raw, index)
    if (block !== null) {
      blocks.push(block)
    }
  }
  return blocks
}

function coerceBlock(raw: unknown, index: number): Block | null {
  if (typeof raw !== 'object' || raw === null) {
    return null
  }

  const candidate = raw as Record<string, unknown>
  const type = candidate.type
  if (typeof type !== 'string' || !isBlockType(type)) {
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
    case 'hero':
      return {
        id,
        type,
        data: {
          variant: isHeroVariant(record.variant) ? record.variant : 'standard',
          heading: typeof record.heading === 'string' ? record.heading : '',
          kicker: optionalString(record, 'kicker'),
          lead: optionalString(record, 'lead'),
          ctaLabel: optionalString(record, 'ctaLabel'),
          ctaUrl: optionalString(record, 'ctaUrl'),
          ghostLabel: optionalString(record, 'ghostLabel'),
          ghostUrl: optionalString(record, 'ghostUrl'),
          media: coerceMedia(record.media),
        },
      }
  }
}

/** Serialize the editor's blocks to the stored JSON string, dropping empty optionals. */
export function serializeBlocksDocument(blocks: Block[]): string {
  const normalized = blocks.map((block): Block => {
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
    return block
  })
  return JSON.stringify(normalized)
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
  }
}

/**
 * Allowlist safe link targets for rendering as an href; blocks `javascript:` /
 * `data:` and other schemes that could execute script. Mirrors the server's
 * `isSafeUrl`, but treats empty as unsafe (the consumer simply omits the link).
 */
export function isSafeHref(url: string): boolean {
  const trimmed = url.trim()
  if (trimmed === '') {
    return false
  }
  return /^(https?:\/\/|mailto:|\/|#)/i.test(trimmed)
}

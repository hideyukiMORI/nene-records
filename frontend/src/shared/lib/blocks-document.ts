/**
 * Typed post-block document model (epic #486 S1b).
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

const BLOCK_TYPES = ['text', 'callout'] as const
export type BlockType = (typeof BLOCK_TYPES)[number]

export const CALLOUT_KINDS = ['info', 'warn', 'ok', 'danger'] as const
export type CalloutKind = (typeof CALLOUT_KINDS)[number]

export interface TextBlockData {
  markdown: string
}

export interface CalloutBlockData {
  kind: CalloutKind
  body: string
  title?: string
}

export type Block =
  | { id: string; type: 'text'; data: TextBlockData }
  | { id: string; type: 'callout'; data: CalloutBlockData }

export type BlockValidationCode = 'markdown-required' | 'body-required' | 'kind-invalid'

function isBlockType(value: string): value is BlockType {
  return (BLOCK_TYPES as readonly string[]).includes(value)
}

function isCalloutKind(value: unknown): value is CalloutKind {
  return typeof value === 'string' && (CALLOUT_KINDS as readonly string[]).includes(value)
}

/** Client-generated stable key (React key / future anchor); opaque, <= 64 chars. */
function newBlockId(): string {
  return globalThis.crypto.randomUUID()
}

export function createBlock(type: BlockType): Block {
  if (type === 'text') {
    return { id: newBlockId(), type, data: { markdown: '' } }
  }
  return { id: newBlockId(), type, data: { kind: 'info', body: '', title: '' } }
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

  if (type === 'text') {
    return {
      id,
      type,
      data: { markdown: typeof record.markdown === 'string' ? record.markdown : '' },
    }
  }

  const kind = isCalloutKind(record.kind) ? record.kind : 'info'
  const title = typeof record.title === 'string' ? record.title : undefined
  return {
    id,
    type,
    data: {
      kind,
      body: typeof record.body === 'string' ? record.body : '',
      ...(title !== undefined && title !== '' ? { title } : {}),
    },
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
        data: {
          kind,
          body,
          ...(title !== undefined && title.trim() !== '' ? { title } : {}),
        },
      }
    }
    return block
  })
  return JSON.stringify(normalized)
}

/** UX-only per-block validation; the server remains the trust boundary. */
export function validateBlock(block: Block): BlockValidationCode | null {
  if (block.type === 'text') {
    return block.data.markdown.trim() === '' ? 'markdown-required' : null
  }
  if (!isCalloutKind(block.data.kind)) {
    return 'kind-invalid'
  }
  return block.data.body.trim() === '' ? 'body-required' : null
}

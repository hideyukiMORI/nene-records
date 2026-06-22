export interface MarkdownHeading {
  /** Heading level: 1 for `#`, 2 for `##`, … 6 for `######`. */
  depth: number
  /** Visible heading text (inline markdown stripped). */
  text: string
  /** Unique slug used as the heading's anchor id. */
  slug: string
}

/**
 * Creates a stateful slugger that turns heading text into a URL-safe slug and
 * de-duplicates repeats by appending `-1`, `-2`, … (GitHub-style).
 */
export function createSlugger(): (text: string) => string {
  const seen = new Map<string, number>()

  return (text: string): string => {
    const base =
      text
        .toLowerCase()
        .trim()
        .replace(/[^\p{L}\p{N}\s-]/gu, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '') || 'section'

    const count = seen.get(base) ?? 0
    seen.set(base, count + 1)
    return count === 0 ? base : `${base}-${String(count)}`
  }
}

/** Removes the common inline markdown markers from a heading's raw text. */
function stripInlineMarkdown(text: string): string {
  return text
    .replace(/!?\[([^\]]*)\]\([^)]*\)/g, '$1') // links / images → label
    .replace(/[*_`~]/g, '') // emphasis / code / strike markers
    .replace(/\s+/g, ' ')
    .trim()
}

/**
 * Extracts ATX headings (`#`…`######` at line start) from markdown in document
 * order, assigning each a unique slug. Returns every level (1–6) so the slug
 * numbering matches what the renderer assigns; callers filter by depth.
 *
 * Fenced code blocks (``` / ~~~) are skipped so `# comment` lines inside code
 * are not treated as headings.
 */
export function extractHeadings(markdown: string): MarkdownHeading[] {
  const slugify = createSlugger()
  const headings: MarkdownHeading[] = []
  let inFence = false

  for (const rawLine of markdown.split('\n')) {
    const trimmed = rawLine.trim()

    if (/^(```|~~~)/.test(trimmed)) {
      inFence = !inFence
      continue
    }
    if (inFence) {
      continue
    }

    const match = /^(#{1,6})\s+(.+?)\s*#*\s*$/.exec(rawLine)
    if (match === null) {
      continue
    }

    const depth = (match[1] ?? '').length
    const text = stripInlineMarkdown(match[2] ?? '')
    if (text === '') {
      continue
    }

    headings.push({ depth, text, slug: slugify(text) })
  }

  return headings
}

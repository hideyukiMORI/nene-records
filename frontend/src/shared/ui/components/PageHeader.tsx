import type { ReactNode } from 'react'
import { Stack } from '../primitives/Stack'
import { Text } from '../primitives/Text'

export interface PageHeaderProps {
  /** Small accent eyebrow above the title (Console redesign §05 — `.rd-eyebrow`). */
  eyebrow?: ReactNode
  /** The page title — always rendered as the H1 via the shared Text primitive. */
  title: ReactNode
  /** Optional muted description below the title (`.rd-sub`). */
  description?: ReactNode
  /** Optional actions pinned to the top-right (typically Button(s)). */
  actions?: ReactNode
}

/**
 * Standard admin page header: accent eyebrow + H1 + optional description, with
 * an optional top-right actions slot. Every page should use this so the eyebrow,
 * title, and description share one source of colour and type scale.
 */
export function PageHeader({ eyebrow, title, description, actions }: PageHeaderProps) {
  return (
    <div className="flex items-start justify-between gap-5">
      <Stack gap="xs">
        {eyebrow !== undefined ? <SectionHeader tone="accent">{eyebrow}</SectionHeader> : null}
        <Text as="h1" variant="heading-md">
          {title}
        </Text>
        {description !== undefined ? <Text muted>{description}</Text> : null}
      </Stack>
      {actions !== undefined ? (
        <div className="flex shrink-0 items-center gap-2">{actions}</div>
      ) : null}
    </div>
  )
}

export interface SectionHeaderProps {
  children: ReactNode
  /** `accent` for a page eyebrow, `muted` (default) for a section overline. */
  tone?: 'accent' | 'muted'
}

/**
 * Small chrome overline used as a page eyebrow (accent) or a section header
 * (muted) — Console redesign §05 `.rd-eyebrow` / `.rd-section-h` / `.pf-panel__h`.
 */
export function SectionHeader({ children, tone = 'muted' }: SectionHeaderProps) {
  const toneClass = tone === 'accent' ? 'text-accent' : 'text-text-muted'
  return (
    <p className={`font-chrome text-tiny font-bold uppercase tracking-widest ${toneClass}`}>
      {children}
    </p>
  )
}

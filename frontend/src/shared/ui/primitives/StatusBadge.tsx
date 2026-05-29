export type StatusBadgeKind = 'draft' | 'published' | 'scheduled' | 'archived'

export interface StatusBadgeProps {
  /** Entity status — selects the tinted pill colour. */
  status: StatusBadgeKind
  /** Visible label (already localized by the caller). */
  children: React.ReactNode
  className?: string
}

const KIND_CLASS: Record<StatusBadgeKind, string> = {
  draft: 'status-badge--draft',
  published: 'status-badge--published',
  scheduled: 'status-badge--scheduled',
  archived: 'status-badge--archived',
}

/**
 * Status badge (Console redesign §05): a tinted pill with a leading dot.
 * Colours come from the `--color-ok/warn/info` theme tokens via `badges.css`,
 * so the badge adapts to every theme.
 */
export function StatusBadge({ status, children, className }: StatusBadgeProps) {
  const classes = ['status-badge', KIND_CLASS[status], className].filter(Boolean).join(' ')
  return (
    <span className={classes}>
      <span className="status-badge__dot" aria-hidden="true" />
      {children}
    </span>
  )
}

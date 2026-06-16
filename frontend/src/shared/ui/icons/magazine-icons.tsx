/**
 * Magazine-specific glyphs for the public site that aren't in the shared admin
 * icon set. Stroke uses currentColor so CSS controls the color per context.
 */
interface MagazineIconProps {
  size?: number
  className?: string
}

function base(size: number, className: string | undefined) {
  return {
    width: size,
    height: size,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 2,
    strokeLinecap: 'round' as const,
    strokeLinejoin: 'round' as const,
    className,
    'aria-hidden': true,
  }
}

export function IconArrow({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <line x1="5" y1="12" x2="19" y2="12" />
      <polyline points="13,6 19,12 13,18" />
    </svg>
  )
}

export function IconArrowLeft({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <line x1="19" y1="12" x2="5" y2="12" />
      <polyline points="11,6 5,12 11,18" />
    </svg>
  )
}

export function IconArrowUpRight({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <line x1="7" y1="17" x2="17" y2="7" />
      <polyline points="8,7 17,7 17,16" />
    </svg>
  )
}

/** "Match system" — a circle filled on one half. */
export function IconAuto({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <circle cx="12" cy="12" r="9" />
      <path d="M12 3 a9 9 0 0 1 0 18 z" fill="currentColor" stroke="none" />
    </svg>
  )
}

export function IconInbox({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <polyline points="22,12 16,12 14,15 10,15 8,12 2,12" />
      <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
    </svg>
  )
}

export function IconList({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <line x1="8" y1="6" x2="21" y2="6" />
      <line x1="8" y1="12" x2="21" y2="12" />
      <line x1="8" y1="18" x2="21" y2="18" />
      <line x1="3" y1="6" x2="3.01" y2="6" />
      <line x1="3" y1="12" x2="3.01" y2="12" />
      <line x1="3" y1="18" x2="3.01" y2="18" />
    </svg>
  )
}

export function IconGrid({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <rect x="3" y="3" width="7" height="7" rx="1" />
      <rect x="14" y="3" width="7" height="7" rx="1" />
      <rect x="3" y="14" width="7" height="7" rx="1" />
      <rect x="14" y="14" width="7" height="7" rx="1" />
    </svg>
  )
}

export function IconUser({ size = 16, className }: MagazineIconProps) {
  return (
    <svg {...base(size, className)}>
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21a8 8 0 0 1 16 0" />
    </svg>
  )
}

/**
 * Public-page layout presets (Phase 1: scaffold selection). Mirrors the backend
 * whitelist in `src/Layout/PublicLayouts.php` — keep the two in sync.
 *
 * - standard: header / single column / footer (default)
 * - full:     header / full-width content / footer
 * - bare:     no header/footer, no theme — fully custom page
 */
export type PublicLayoutKey = 'standard' | 'full' | 'bare'

export const PUBLIC_LAYOUT_KEYS: readonly PublicLayoutKey[] = ['standard', 'full', 'bare']

export const DEFAULT_PUBLIC_LAYOUT: PublicLayoutKey = 'standard'

function isLayoutKey(value: string | null | undefined): value is PublicLayoutKey {
  return value != null && (PUBLIC_LAYOUT_KEYS as readonly string[]).includes(value)
}

/**
 * Resolve the effective layout: per-entity override wins, then the type default,
 * then the global default. Unknown values fall through.
 */
export function resolveLayout(
  entityLayout: string | null | undefined,
  typeDefaultLayout: string | null | undefined,
): PublicLayoutKey {
  if (isLayoutKey(entityLayout)) {
    return entityLayout
  }

  if (isLayoutKey(typeDefaultLayout)) {
    return typeDefaultLayout
  }

  return DEFAULT_PUBLIC_LAYOUT
}

/**
 * Public-page layout presets. Mirrors the backend whitelist in
 * `src/Layout/PublicLayouts.php` — keep the two in sync.
 *
 * - standard:  header / single column / footer (default)
 * - full:      header / full-width content / footer
 * - two-col:   header / [main | sidebar] / footer
 * - three-col: header / [main | sidebar | aside] / footer
 * - bare:      no header/footer, no theme — fully custom page
 * - custom:    full-width host for a sandboxed bundle field + crawlable text
 */
export type PublicLayoutKey = 'standard' | 'full' | 'two-col' | 'three-col' | 'bare' | 'custom'

export const PUBLIC_LAYOUT_KEYS: readonly PublicLayoutKey[] = [
  'standard',
  'full',
  'two-col',
  'three-col',
  'bare',
  'custom',
]

export const DEFAULT_PUBLIC_LAYOUT: PublicLayoutKey = 'standard'

/** Content regions a field can be placed into (mirrors ContentRegions.php). */
export type ContentRegion = 'main' | 'sidebar' | 'aside'

export const CONTENT_REGIONS: readonly ContentRegion[] = ['main', 'sidebar', 'aside']

export const DEFAULT_REGION: ContentRegion = 'main'

/**
 * Regions a widget can be placed into (mirrors WidgetRegions.php). Unlike field
 * regions, widgets place into the site chrome (header/footer) and side columns;
 * `main` is record content, not a widget target.
 */
export type WidgetRegion = 'header' | 'sidebar' | 'aside' | 'footer'

export const WIDGET_REGIONS: readonly WidgetRegion[] = ['header', 'sidebar', 'aside', 'footer']

/** Regions each layout renders, in order (mirrors PublicLayouts::regions). */
const LAYOUT_REGIONS: Record<PublicLayoutKey, readonly ContentRegion[]> = {
  standard: ['main'],
  full: ['main'],
  'two-col': ['main', 'sidebar'],
  'three-col': ['main', 'sidebar', 'aside'],
  bare: ['main'],
  custom: ['main'],
}

export function layoutRegions(layout: PublicLayoutKey): readonly ContentRegion[] {
  return LAYOUT_REGIONS[layout]
}

/** Normalize a field's region to one the active layout renders (else `main`). */
export function regionForLayout(
  region: string | null | undefined,
  layout: PublicLayoutKey,
): ContentRegion {
  const regions = LAYOUT_REGIONS[layout]
  return region != null && (regions as readonly string[]).includes(region)
    ? (region as ContentRegion)
    : DEFAULT_REGION
}

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

/**
 * On-demand font availability (#709).
 *
 * The Tier A release ZIP ships only a minimal font set; the rest travels in a
 * separate "font pack" ZIP that a self-host installer may or may not have applied
 * yet (see `tools/build-release.sh` step 3b). Docker/production builds bundle
 * every font, so this module must degrade to "everything available" whenever the
 * runtime probe cannot prove otherwise — never nag on a full install.
 *
 * `BASE_BUNDLED_FONT_FAMILIES` MUST stay in sync with `KEEP_FONT_FAMILIES` in
 * `tools/build-release.sh` (the split allowlist).
 */

/**
 * @font-face families guaranteed to ship inside the base release ZIP: the admin
 * UI faces (see `src/fonts.ts`), the default public theme `consumer`
 * (Bricolage Grotesque), and the #818 public-site faces. Family names here match
 * the CSS `font-family` used by @fontsource / the #818 subset.
 */
export const BASE_BUNDLED_FONT_FAMILIES: ReadonlySet<string> = new Set([
  'Inter',
  'Saira Semi Condensed',
  'Space Grotesk',
  'JetBrains Mono',
  'Bricolage Grotesque',
  'Zen Kaku Gothic New',
  'IBM Plex Mono',
])

/**
 * Map a font-picker option `value` (see `theme-customization.ts`) to its primary
 * @font-face family. `system` (and any unlisted value) resolves to `undefined` =
 * no webfont required, so it is always considered available.
 */
export const FONT_VALUE_TO_FAMILY: Readonly<Record<string, string>> = {
  inter: 'Inter',
  roboto: 'Roboto',
  'source-serif': 'Source Serif 4',
  saira: 'Saira Semi Condensed',
  'space-grotesk': 'Space Grotesk',
  playfair: 'Playfair Display',
  bricolage: 'Bricolage Grotesque',
  archivo: 'Archivo',
  oswald: 'Oswald',
  'space-mono': 'Space Mono',
}

/**
 * True when a picker option is guaranteed present in the base ZIP (or needs no
 * webfont at all). Options for which this is false require the on-demand font
 * pack on a Tier A install.
 */
export function isBaseBundledFontValue(value: string | undefined): boolean {
  if (value === undefined || value === '') return true
  const family = FONT_VALUE_TO_FAMILY[value]
  return family === undefined || BASE_BUNDLED_FONT_FAMILIES.has(family)
}

/**
 * Static marker the Tier A base ZIP drops at the app docroot (`font-pack.json`).
 * `build-release.sh` writes `{"complete": false}` into the base ZIP and
 * `{"complete": true}` into the font pack, which overwrites it on install.
 * Resolved relative to `document.baseURI` so subdirectory installs work.
 */
const FONT_PACK_MARKER = 'font-pack.json'

/**
 * Probe whether the on-demand font pack is installed. The admin font picker does
 * not itself load the public/theme @font-face rules (those live in the public
 * shell / preview iframe), so a Font Loading API check would be blind here.
 * Instead we fetch the docroot marker that `build-release.sh` ships:
 *
 * - Tier A base-only install → marker served with `complete: false` → not installed.
 * - Tier A base + font pack   → pack overwrote the marker to `complete: true`.
 * - Docker / production / dev → no marker file (front controller 404s) → treated
 *   as installed, so a full build never nags.
 *
 * Any fetch/parse failure resolves to `true` (assume installed) so SSR, tests,
 * and non-Tier-A installs never flag fonts.
 */
export async function probeFontPackInstalled(): Promise<boolean> {
  if (typeof fetch === 'undefined' || typeof document === 'undefined') return true
  try {
    const response = await fetch(new URL(FONT_PACK_MARKER, document.baseURI), {
      headers: { Accept: 'application/json' },
    })
    if (!response.ok) return true
    const data: unknown = await response.json()
    if (data !== null && typeof data === 'object' && 'complete' in data) {
      return (data as { complete?: unknown }).complete !== false
    }
    return true
  } catch {
    return true
  }
}

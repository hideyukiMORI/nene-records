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
 * Representative pack-only family. The font pack is applied atomically (one ZIP),
 * so probing a single non-base family is enough to decide whether the whole pack
 * is present. Playfair Display is a common display pick and pack-only.
 */
const PACK_PROBE_FAMILY = 'Playfair Display'

/**
 * Probe whether the on-demand font pack is installed. Attempts to load a
 * representative pack-only face: on a full install the woff2 exists and the load
 * resolves; on a base-only Tier A install the file 404s and the load rejects.
 *
 * Returns `true` (assume installed → do not nag) whenever the CSS Font Loading
 * API is unavailable (SSR, jsdom) so tests and server render never flag fonts.
 */
export async function probeFontPackInstalled(): Promise<boolean> {
  if (typeof document === 'undefined' || !('fonts' in document)) return true
  try {
    // `document.fonts.load` fetches the matching @font-face; a missing woff2
    // rejects → pack absent. A resolved load (with or without matched faces)
    // means the file is reachable → pack present.
    await document.fonts.load(`16px "${PACK_PROBE_FAMILY}"`)
    return true
  } catch {
    return false
  }
}

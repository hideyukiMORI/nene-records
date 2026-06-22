import postcss, { type AtRule, type ChildNode } from 'postcss'
import { findDataUriSvgIssues } from './sanitize-svg'
import type { ValidationIssue } from './validate-manifest'

export interface ThemeCssOptions {
  /** The theme id — selectors must scope to this theme (or `.nene-public`). */
  themeId: string
}

// External resource schemes that must never appear in a theme's url() — assets
// are bundled and served same-origin (contract §8.4). `data:` and relative
// paths and svg fragments (#id) are allowed.
const EXTERNAL_URL = /url\(\s*['"]?\s*(https?:|\/\/|ftp:|javascript:)/i
// Constructs that can execute or pull remote code — rejected outright.
const DANGEROUS = /(javascript:|expression\s*\(|-moz-binding|behavior\s*:)/i

function selectorIsScoped(selector: string, themeId: string): boolean {
  const part = selector.trim()
  if (part.includes('.nene-public')) {
    return true
  }
  const match = /\[data-theme=['"]?([a-z0-9-]+)['"]?/i.exec(part)
  if (match === null) {
    return false
  }
  const value = match[1]
  return value === themeId || value === `${themeId}-dark`
}

function isKeyframeChild(node: ChildNode): boolean {
  const parent = node.parent
  return parent?.type === 'atrule' && /^(-\w+-)?keyframes$/i.test((parent as AtRule).name)
}

/**
 * Validate a theme stylesheet (tokens.css / components.css). Enforces the
 * contract's CSS rules (§8.4 / §10):
 *  - every rule is scoped to the theme (`[data-theme='<id>'|'<id>-dark']`) or
 *    `.nene-public` — a theme can't leak styles into the admin/other themes;
 *  - no `@import` (no remote or local stylesheet pulls);
 *  - no external `url()` (assets are bundled / data URIs only);
 *  - no executable constructs (`javascript:`, `expression()`, …);
 *  - `data:image/svg+xml` URIs are decoded and screened for active content
 *    (<script>, on* handlers, external href) — see {@link findDataUriSvgIssues}.
 *
 * This is the author-facing CI gate. The authoritative SVG sanitiser is
 * server-side (`src/Media/SvgSanitizer.php`, applied on media upload). Contrast
 * AA is checked separately server-side (`src/Theme/ColorContrast.php`).
 */
export function validateThemeCss(css: string, { themeId }: ThemeCssOptions): ValidationIssue[] {
  const issues: ValidationIssue[] = []

  let root
  try {
    root = postcss.parse(css)
  } catch (error) {
    return [{ path: '(css)', message: `parse error: ${(error as Error).message}` }]
  }

  root.walkAtRules((atRule) => {
    if (atRule.name.toLowerCase() === 'import') {
      issues.push({ path: `@import`, message: '@import is not allowed in theme CSS' })
    }
  })

  root.walkRules((rule) => {
    if (isKeyframeChild(rule)) {
      return
    }
    for (const selector of rule.selectors) {
      if (!selectorIsScoped(selector, themeId)) {
        issues.push({
          path: selector,
          message: `selector is not scoped to the theme (.nene-public or [data-theme='${themeId}'])`,
        })
      }
    }
  })

  root.walkDecls((decl) => {
    const value = decl.value
    if (EXTERNAL_URL.test(value)) {
      issues.push({
        path: decl.prop,
        message: 'external url() is not allowed — bundle assets or use a data URI',
      })
    }
    if (DANGEROUS.test(value)) {
      issues.push({ path: decl.prop, message: 'value contains a disallowed construct' })
    }
    for (const message of findDataUriSvgIssues(value)) {
      issues.push({ path: decl.prop, message })
    }
  })

  return issues
}

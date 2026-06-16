/**
 * Theme bundle validator CLI (epic #367 / Phase 3 #373, slice 1).
 *
 * Validates every theme manifest under docs/theming (against the JSON Schema)
 * and any sibling tokens.css / components.css (scope + safety). Exits non-zero
 * on any issue so it can gate CI via `npm run validate:themes`.
 *
 * Run from the frontend project root: `tsx scripts/validate-themes.ts`.
 */
import { existsSync, readdirSync, readFileSync } from 'node:fs'
import { basename, dirname, join, resolve } from 'node:path'
import {
  validateManifest,
  validateThemeCss,
  type ValidationIssue,
} from '../src/shared/lib/theme-bundle'

const THEMING_DIR = resolve(process.cwd(), '../docs/theming')
const SCHEMA_PATH = join(THEMING_DIR, 'public-theme.schema.json')

function readJson(path: string): Record<string, unknown> {
  return JSON.parse(readFileSync(path, 'utf8')) as Record<string, unknown>
}

/** Recursively collect every *.manifest.json under the theming dir. */
function findManifests(dir: string): string[] {
  const out: string[] = []
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    const full = join(dir, entry.name)
    if (entry.isDirectory()) {
      out.push(...findManifests(full))
    } else if (entry.name === 'manifest.json' || entry.name.endsWith('.manifest.json')) {
      out.push(full)
    }
  }
  return out
}

function report(label: string, issues: ValidationIssue[]): boolean {
  if (issues.length === 0) {
    console.log(`  ✓ ${label}`)
    return true
  }
  console.error(`  ✗ ${label}`)
  for (const issue of issues) {
    console.error(`      ${issue.path} — ${issue.message}`)
  }
  return false
}

function main(): void {
  const schema = readJson(SCHEMA_PATH)
  const manifests = findManifests(THEMING_DIR)

  if (manifests.length === 0) {
    console.log('No theme manifests found — nothing to validate.')
    return
  }

  let ok = true
  for (const manifestPath of manifests) {
    const rel = manifestPath.slice(THEMING_DIR.length + 1)
    console.log(`\n${rel}`)
    const manifest = readJson(manifestPath)
    ok = report('manifest', validateManifest(manifest, schema)) && ok

    const themeId = typeof manifest.id === 'string' ? manifest.id : ''
    for (const cssName of ['tokens.css', 'components.css']) {
      const cssPath = join(dirname(manifestPath), cssName)
      if (existsSync(cssPath)) {
        const css = readFileSync(cssPath, 'utf8')
        ok =
          report(`${basename(cssPath)} (theme: ${themeId})`, validateThemeCss(css, { themeId })) &&
          ok
      }
    }
  }

  if (!ok) {
    console.error('\nTheme validation failed.')
    process.exit(1)
  }
  console.log('\nAll theme bundles valid.')
}

main()

import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { validateManifest } from './validate-manifest'

// vitest runs with cwd at the frontend project root; docs live one level up.
function readJson(fromFrontend: string): Record<string, unknown> {
  return JSON.parse(readFileSync(resolve(process.cwd(), fromFrontend), 'utf8')) as Record<
    string,
    unknown
  >
}

const schema = readJson('../docs/theming/public-theme.schema.json')
const exampleManifest = readJson('../docs/theming/examples/consumer.manifest.json')
const auroraManifest = readJson('../docs/theming/themes/aurora/manifest.json')

describe('validateManifest', () => {
  it('accepts the built-in consumer example manifest', () => {
    expect(validateManifest(exampleManifest, schema)).toEqual([])
  })

  it('accepts the Aurora theme manifest', () => {
    expect(validateManifest(auroraManifest, schema)).toEqual([])
  })

  it('rejects a manifest missing a required token (no color-accent in dark)', () => {
    const broken = structuredClone(exampleManifest)
    const tokens = broken.tokens as { dark: Record<string, unknown> }
    delete tokens.dark['color-accent']
    expect(validateManifest(broken, schema).length).toBeGreaterThan(0)
  })

  it('rejects a manifest that only supports light mode', () => {
    const broken = structuredClone(exampleManifest)
    broken.supportsModes = ['light']
    expect(validateManifest(broken, schema).length).toBeGreaterThan(0)
  })

  it('rejects an external asset URL in the manifest', () => {
    const broken = structuredClone(exampleManifest)
    broken.assets = { hero: 'https://evil.example/hero.png' }
    expect(validateManifest(broken, schema).length).toBeGreaterThan(0)
  })
})

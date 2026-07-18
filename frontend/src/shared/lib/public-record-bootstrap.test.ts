import { afterEach, describe, expect, it } from 'vitest'
import { readPublicRecordBootstrap } from './public-record-bootstrap'

const SCRIPT_ID = 'nene-records-public-record-bootstrap'

function injectBootstrapScript(content: string): void {
  const script = document.createElement('script')
  script.id = SCRIPT_ID
  script.type = 'application/json'
  script.textContent = content
  document.body.appendChild(script)
}

afterEach(() => {
  document.getElementById(SCRIPT_ID)?.remove()
})

describe('readPublicRecordBootstrap', () => {
  it('returns null when the bootstrap script tag is absent (plain SPA load)', () => {
    expect(readPublicRecordBootstrap()).toBeNull()
  })

  it('returns null for an empty or whitespace-only payload', () => {
    injectBootstrapScript('   \n  ')
    expect(readPublicRecordBootstrap()).toBeNull()
  })

  it('returns null for corrupted JSON instead of throwing', () => {
    injectBootstrapScript('{"entityId": 5,')
    expect(readPublicRecordBootstrap()).toBeNull()
  })

  it('parses a valid payload', () => {
    injectBootstrapScript('{"entityId": 5, "entityTypeSlug": "pages", "canonicalPath": "/company"}')
    const bootstrap = readPublicRecordBootstrap()
    expect(bootstrap?.entityId).toBe(5)
    expect(bootstrap?.entityTypeSlug).toBe('pages')
    expect(bootstrap?.canonicalPath).toBe('/company')
  })
})

import { cleanup, render } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { PublicSettingItem } from '@/entities/setting'
import type { Widget } from '@/entities/widget'

// Control the org's public settings (the embed allowlist source) without the
// network. `publicSettingsToMap` stays real so the component's own parsing runs.
let settingsItems: PublicSettingItem[] = []
vi.mock('@/entities/setting', async (importActual) => {
  const actual = await importActual<typeof import('@/entities/setting')>()
  return {
    ...actual,
    usePublicSettings: () => ({ data: { items: settingsItems } }),
  }
})

const { TrustedEmbedWidget } = await import('./TrustedEmbedWidget')

afterEach(cleanup)

function setAllowlist(origins: string[]): void {
  settingsItems = [{ settingKey: 'embed_allowlist', value: JSON.stringify(origins) }]
}

function makeWidget(settings: Record<string, unknown>): Widget {
  return {
    id: 1,
    widgetType: 'trusted-embed',
    region: 'footer',
    displayOrder: 0,
    title: null,
    settings,
    createdAt: '',
    updatedAt: '',
  }
}

const VALID = {
  origin: 'https://widgets.example.com',
  src: 'https://widgets.example.com/form.js',
  integrity: 'sha384-abcDEF123+/=',
}

describe('TrustedEmbedWidget', () => {
  it('injects the validated script when the origin is allowlisted', () => {
    setAllowlist(['https://widgets.example.com'])
    const { container } = render(<TrustedEmbedWidget widget={makeWidget(VALID)} />)

    const script = container.querySelector('.trusted-embed script')
    expect(script).not.toBeNull()
    expect(script?.getAttribute('src')).toBe('https://widgets.example.com/form.js')
    expect(script?.getAttribute('integrity')).toBe('sha384-abcDEF123+/=')
    expect(script?.getAttribute('crossorigin')).toBe('anonymous')
    expect(script?.hasAttribute('async')).toBe(true)
  })

  it('renders nothing when the origin is not on the allowlist', () => {
    setAllowlist(['https://other.example.org'])
    const { container } = render(<TrustedEmbedWidget widget={makeWidget(VALID)} />)

    expect(container.querySelector('.trusted-embed')).toBeNull()
    expect(container.querySelector('script')).toBeNull()
  })

  it('renders nothing when the SRI is missing', () => {
    setAllowlist(['https://widgets.example.com'])
    const noSri: Record<string, unknown> = { ...VALID }
    delete noSri['integrity']
    const { container } = render(<TrustedEmbedWidget widget={makeWidget(noSri)} />)

    expect(container.querySelector('.trusted-embed')).toBeNull()
  })
})

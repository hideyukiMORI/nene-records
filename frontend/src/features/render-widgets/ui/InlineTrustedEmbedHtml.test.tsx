import { cleanup, render } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { PublicSettingItem } from '@/entities/setting'
import type { Widget } from '@/entities/widget'

// Control the org's public settings + widgets (the two sources the placement
// resolves against) without the network. The parsing/validation stays real.
let settingsItems: PublicSettingItem[] = []
let widgetItems: Widget[] = []

vi.mock('@/entities/setting', async (importActual) => {
  const actual = await importActual<typeof import('@/entities/setting')>()
  return { ...actual, usePublicSettings: () => ({ data: { items: settingsItems } }) }
})
vi.mock('@/entities/widget', async (importActual) => {
  const actual = await importActual<typeof import('@/entities/widget')>()
  return { ...actual, usePublicWidgets: () => ({ data: { items: widgetItems } }) }
})

const { InlineTrustedEmbedHtml } = await import('./InlineTrustedEmbedHtml')

afterEach(cleanup)

const ORIGIN = 'https://widgets.example.com'

const VALID: Record<string, unknown> = {
  origin: ORIGIN,
  src: `${ORIGIN}/form.js`,
  integrity: 'sha384-abcDEF123+/=',
}

function setAllowlist(origins: string[]): void {
  settingsItems = [{ settingKey: 'embed_allowlist', value: JSON.stringify(origins) }]
}

function setWidgets(...widgets: Partial<Widget>[]): void {
  widgetItems = widgets.map((w, i) => ({
    id: i + 1,
    widgetType: 'trusted-embed',
    region: 'inline',
    displayOrder: 0,
    title: null,
    settings: VALID,
    createdAt: '',
    updatedAt: '',
    ...w,
  }))
}

const marker = (id = 1): string => `<div data-nene-embed="${String(id)}"></div>`

describe('InlineTrustedEmbedHtml', () => {
  it('injects the validated script at the marker position', () => {
    setAllowlist([ORIGIN])
    setWidgets({ id: 1 })
    const { container } = render(<InlineTrustedEmbedHtml html={`<p>a</p>${marker()}<p>b</p>`} />)

    const script = container.querySelector('[data-nene-embed="1"] script')
    expect(script).not.toBeNull()
    expect(script?.getAttribute('src')).toBe(`${ORIGIN}/form.js`)
    expect(script?.getAttribute('integrity')).toBe('sha384-abcDEF123+/=')
    expect(script?.getAttribute('crossorigin')).toBe('anonymous')
    expect(script?.hasAttribute('async')).toBe(true)
    // The surrounding prose is untouched.
    expect(container.querySelectorAll('p')).toHaveLength(2)
  })

  it('emits the data-* attributes from the widget settings', () => {
    setAllowlist([ORIGIN])
    setWidgets({ id: 1, settings: { ...VALID, attributes: { 'data-form': 'ayane-contact' } } })
    const { container } = render(<InlineTrustedEmbedHtml html={marker()} />)

    expect(container.querySelector('script')?.getAttribute('data-form')).toBe('ayane-contact')
  })

  it('renders no script when the origin is not on the allowlist', () => {
    setAllowlist(['https://other.example.org'])
    setWidgets({ id: 1 })
    const { container } = render(<InlineTrustedEmbedHtml html={marker()} />)

    expect(container.querySelector('script')).toBeNull()
  })

  it('renders no script when the allowlist is empty', () => {
    setAllowlist([])
    setWidgets({ id: 1 })
    const { container } = render(<InlineTrustedEmbedHtml html={marker()} />)

    expect(container.querySelector('script')).toBeNull()
  })

  it('renders no script when the marker points at an unknown widget', () => {
    setAllowlist([ORIGIN])
    setWidgets({ id: 1 })
    const { container } = render(<InlineTrustedEmbedHtml html={marker(99)} />)

    expect(container.querySelector('script')).toBeNull()
  })

  it('does not honour a marker for a region-placed widget', () => {
    // It already renders in its region; honouring the marker would double-load.
    setAllowlist([ORIGIN])
    setWidgets({ id: 1, region: 'footer' })
    const { container } = render(<InlineTrustedEmbedHtml html={marker()} />)

    expect(container.querySelector('script')).toBeNull()
  })

  it('renders no script when the SRI is missing', () => {
    setAllowlist([ORIGIN])
    const noSri: Record<string, unknown> = { ...VALID }
    delete noSri['integrity']
    setWidgets({ id: 1, settings: noSri })
    const { container } = render(<InlineTrustedEmbedHtml html={marker()} />)

    expect(container.querySelector('script')).toBeNull()
  })

  it('renders no script for a cross-origin src', () => {
    setAllowlist([ORIGIN])
    setWidgets({ id: 1, settings: { ...VALID, src: 'https://evil.example.com/form.js' } })
    const { container } = render(<InlineTrustedEmbedHtml html={marker()} />)

    expect(container.querySelector('script')).toBeNull()
  })

  it('emits the same widget only once even when the marker is repeated', () => {
    setAllowlist([ORIGIN])
    setWidgets({ id: 1 })
    const { container } = render(<InlineTrustedEmbedHtml html={`${marker()}<p>x</p>${marker()}`} />)

    expect(container.querySelectorAll('script')).toHaveLength(1)
  })

  it('ignores a marker that is not an empty div (SSR parity)', () => {
    setAllowlist([ORIGIN])
    setWidgets({ id: 1 })
    const { container } = render(
      <InlineTrustedEmbedHtml
        html={'<div data-nene-embed="1">x</div><span data-nene-embed="1"></span>'}
      />,
    )

    expect(container.querySelector('script')).toBeNull()
  })

  it('still strips an authored script tag from the body', () => {
    setAllowlist([ORIGIN])
    setWidgets({ id: 1 })
    const { container } = render(
      <InlineTrustedEmbedHtml html={`<script src="${ORIGIN}/form.js"></script><p>a</p>`} />,
    )

    expect(container.querySelector('script')).toBeNull()
    expect(container.querySelector('p')?.textContent).toBe('a')
  })
})

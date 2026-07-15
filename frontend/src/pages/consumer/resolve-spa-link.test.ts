import { describe, expect, it } from 'vitest'
import { resolveSpaLink, type SpaLinkContext } from './resolve-spa-link'

const CTX: SpaLinkContext = {
  basePath: '',
  origin: 'https://ayane.example',
  currentPath: '/privacy',
}

const PLAIN_CLICK = {
  button: 0,
  metaKey: false,
  ctrlKey: false,
  shiftKey: false,
  altKey: false,
  defaultPrevented: false,
}

function anchor(html: string): HTMLAnchorElement {
  const el = document.createElement('div')
  el.innerHTML = html
  const a = el.querySelector('a')
  if (a === null) {
    throw new Error('fixture must contain an anchor')
  }
  return a
}

describe('resolveSpaLink', () => {
  // The 239 internal links baked into AYANE's bespoke chrome are the whole point:
  // these must stop doing a full document load (#885).
  it.each([
    ['/services', '/services'],
    ['/company/ceo', '/company/ceo'],
    ['/', '/'],
    ['/services?offset=20', '/services?offset=20'],
  ])('routes the internal link %s', (href, expected) => {
    expect(resolveSpaLink(anchor(`<a href="${href}">x</a>`), PLAIN_CLICK, CTX)).toBe(expected)
  })

  // Everything below must stay with the browser. Getting any of these wrong breaks a
  // link that works today, which is worse than the flash we are fixing.
  it('leaves another origin alone', () => {
    expect(
      resolveSpaLink(anchor('<a href="https://github.com/x">x</a>'), PLAIN_CLICK, CTX),
    ).toBeNull()
  })

  it('leaves mailto: and tel: alone', () => {
    expect(resolveSpaLink(anchor('<a href="mailto:a@b.c">x</a>'), PLAIN_CLICK, CTX)).toBeNull()
    expect(resolveSpaLink(anchor('<a href="tel:0474043740">x</a>'), PLAIN_CLICK, CTX)).toBeNull()
  })

  it('leaves uploaded media alone (a real file the server serves)', () => {
    expect(
      resolveSpaLink(anchor('<a href="/media/2026/07/terms.pdf">x</a>'), PLAIN_CLICK, CTX),
    ).toBeNull()
  })

  it.each(['/api/v1/public/records/resolve', '/assets/index.css', '/downloads/x.pdf'])(
    'leaves the server-owned path %s alone',
    (href) => {
      expect(resolveSpaLink(anchor(`<a href="${href}">x</a>`), PLAIN_CLICK, CTX)).toBeNull()
    },
  )

  it('leaves target/_blank and download alone', () => {
    expect(
      resolveSpaLink(anchor('<a href="/services" target="_blank">x</a>'), PLAIN_CLICK, CTX),
    ).toBeNull()
    expect(
      resolveSpaLink(anchor('<a href="/services" download>x</a>'), PLAIN_CLICK, CTX),
    ).toBeNull()
    expect(
      resolveSpaLink(anchor('<a href="/services" rel="external">x</a>'), PLAIN_CLICK, CTX),
    ).toBeNull()
  })

  it('honours target="_self" as a normal in-app link', () => {
    expect(
      resolveSpaLink(anchor('<a href="/services" target="_self">x</a>'), PLAIN_CLICK, CTX),
    ).toBe('/services')
  })

  it.each(['metaKey', 'ctrlKey', 'shiftKey', 'altKey'] as const)(
    'leaves %s clicks to the browser (open in new tab / window)',
    (key) => {
      expect(
        resolveSpaLink(anchor('<a href="/services">x</a>'), { ...PLAIN_CLICK, [key]: true }, CTX),
      ).toBeNull()
    },
  )

  it('leaves middle-click and already-handled clicks alone', () => {
    expect(
      resolveSpaLink(anchor('<a href="/services">x</a>'), { ...PLAIN_CLICK, button: 1 }, CTX),
    ).toBeNull()
    expect(
      resolveSpaLink(
        anchor('<a href="/services">x</a>'),
        { ...PLAIN_CLICK, defaultPrevented: true },
        CTX,
      ),
    ).toBeNull()
  })

  it('lets the browser scroll a same-page hash', () => {
    expect(resolveSpaLink(anchor('<a href="#pricing">x</a>'), PLAIN_CLICK, CTX)).toBeNull()
  })

  it('routes a hash that points at another page', () => {
    expect(resolveSpaLink(anchor('<a href="/services#pricing">x</a>'), PLAIN_CLICK, CTX)).toBe(
      '/services#pricing',
    )
  })

  it('strips the base path so the result is router-relative (#zip-install S2)', () => {
    const sub: SpaLinkContext = { ...CTX, basePath: '/blog', currentPath: '/blog/privacy' }
    expect(resolveSpaLink(anchor('<a href="/blog/services">x</a>'), PLAIN_CLICK, sub)).toBe(
      '/services',
    )
    expect(resolveSpaLink(anchor('<a href="/blog">x</a>'), PLAIN_CLICK, sub)).toBe('/')
    // Outside our install → someone else's app.
    expect(resolveSpaLink(anchor('<a href="/other/page">x</a>'), PLAIN_CLICK, sub)).toBeNull()
  })

  it('ignores an anchor with no href', () => {
    expect(resolveSpaLink(anchor('<a>x</a>'), PLAIN_CLICK, CTX)).toBeNull()
  })
})

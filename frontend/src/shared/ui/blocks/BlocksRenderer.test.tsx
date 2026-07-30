import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { BlocksRenderer } from './BlocksRenderer'

afterEach(cleanup)
afterEach(() => {
  localStorage.removeItem('nene-locale')
})

describe('BlocksRenderer', () => {
  it('renders text and callout blocks', () => {
    const doc = JSON.stringify([
      { id: 'b1', type: 'text', data: { markdown: '# Heading\n\nBody text.' } },
      { id: 'b2', type: 'callout', data: { kind: 'warn', title: 'Heads up', body: 'Be careful.' } },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    expect(screen.getByRole('heading', { name: 'Heading' })).toBeInTheDocument()
    expect(screen.getByText('Body text.')).toBeInTheDocument()
    const callout = container.querySelector('.callout')
    expect(callout).not.toBeNull()
    expect(callout?.getAttribute('data-callout-kind')).toBe('warn')
    expect(screen.getByText('Heads up')).toBeInTheDocument()
  })

  it('renders nothing for an empty or invalid document', () => {
    const { container: empty } = renderWithProviders(<BlocksRenderer documentJson="[]" />)
    expect(empty.querySelector('.blocks')).toBeNull()

    const { container: invalid } = renderWithProviders(<BlocksRenderer documentJson="not json" />)
    expect(invalid.querySelector('.blocks')).toBeNull()
  })

  it('renders a hero block with emphasis and drops unsafe CTA links', () => {
    const doc = JSON.stringify([
      {
        id: 'h1',
        type: 'hero',
        data: {
          variant: 'standard',
          kicker: 'Spring',
          heading: 'New *releases*',
          lead: 'Lead.',
          ctaLabel: 'Browse',
          ctaUrl: '/releases',
          ghostLabel: 'Bad',
          ghostUrl: 'javascript:alert(1)',
        },
      },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    const hero = container.querySelector('.hero--block')
    expect(hero?.getAttribute('data-hero')).toBe('standard')
    expect(container.querySelector('.hero__title em')?.textContent).toBe('releases')

    const safe = screen.getByRole('link', { name: 'Browse' })
    expect(safe.getAttribute('href')).toBe('/releases')
    expect(screen.queryByText('Bad')).toBeNull()
  })

  it('renders the hero art image from stored media', () => {
    const doc = JSON.stringify([
      {
        id: 'h1',
        type: 'hero',
        data: {
          variant: 'standard',
          heading: 'X',
          media: { mediaId: '7', url: '/media/2026/06/a.png', alt: 'Cover' },
        },
      },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    const img = container.querySelector('.hero__art img')
    expect(img).not.toBeNull()
    expect(img?.getAttribute('alt')).toBe('Cover')
  })

  it('renders gallery slides with captions', () => {
    const doc = JSON.stringify([
      {
        id: 'g',
        type: 'gallery',
        data: {
          layout: 'carousel',
          items: [
            { mediaId: '1', url: '/media/2026/06/a.png', alt: 'First', caption: 'Cap1' },
            { mediaId: '2', url: '/media/2026/06/b.png', alt: 'Second' },
          ],
        },
      },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    expect(container.querySelector('.gallery--carousel')).not.toBeNull()
    expect(container.querySelectorAll('.gallery__slide')).toHaveLength(2)
    expect(screen.getByText('Cap1')).toBeInTheDocument()
    expect(container.querySelector('img[alt="First"]')).not.toBeNull()
  })

  it('renders a chart as SVG plus an sr-only data table', () => {
    const doc = JSON.stringify([
      {
        id: 'k',
        type: 'chart',
        data: {
          chartType: 'bar',
          title: 'Monthly',
          series: [
            { label: 'Jan', value: 4 },
            { label: 'Feb', value: 6 },
          ],
          summary: 'Up from Jan to Feb.',
        },
      },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    expect(container.querySelector('.chart[data-chart-type="bar"]')).not.toBeNull()
    expect(container.querySelectorAll('.chart__bar')).toHaveLength(2)
    expect(screen.getByText('Up from Jan to Feb.')).toBeInTheDocument()
    expect(container.querySelectorAll('.chart__table tbody tr')).toHaveLength(2)
  })

  it('renders a group container with its leaf children and tone', () => {
    const doc = JSON.stringify([
      {
        id: 'g1',
        type: 'group',
        data: {
          tone: 'card',
          children: [{ id: 'c1', type: 'callout', data: { kind: 'info', body: 'Grouped note' } }],
        },
      },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    const group = container.querySelector('.group')
    expect(group?.getAttribute('data-group-tone')).toBe('card')
    expect(group?.querySelector('.callout')).not.toBeNull()
    expect(screen.getByText('Grouped note')).toBeInTheDocument()
  })

  it('renders a columns block with one rendered column per data column', () => {
    const doc = JSON.stringify([
      {
        id: 'cols',
        type: 'columns',
        data: {
          columns: [
            { children: [{ id: 'a', type: 'text', data: { markdown: 'Left col' } }] },
            { children: [{ id: 'b', type: 'text', data: { markdown: 'Right col' } }] },
          ],
        },
      },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    const columns = container.querySelector('.columns')
    expect(columns?.getAttribute('data-columns')).toBe('2')
    expect(container.querySelectorAll('.columns__col')).toHaveLength(2)
    expect(screen.getByText('Left col')).toBeInTheDocument()
    expect(screen.getByText('Right col')).toBeInTheDocument()
  })

  it('renders spacer (with size) and divider blocks', () => {
    const doc = JSON.stringify([
      { id: 's', type: 'spacer', data: { size: 'lg' } },
      { id: 'd', type: 'divider', data: {} },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)

    expect(container.querySelector('.spacer')?.getAttribute('data-spacer-size')).toBe('lg')
    expect(container.querySelector('hr.divider')).not.toBeNull()
  })

  it('localizes the callout screen-reader prefix by locale', () => {
    const doc = JSON.stringify([{ id: 'c', type: 'callout', data: { kind: 'danger', body: 'x' } }])

    localStorage.setItem('nene-locale', 'ja')
    const { container: ja } = renderWithProviders(<BlocksRenderer documentJson={doc} />)
    expect(ja.querySelector('.callout .sr-only')?.textContent).toBe('エラー: ')
    cleanup()

    localStorage.setItem('nene-locale', 'en')
    const { container: en } = renderWithProviders(<BlocksRenderer documentJson={doc} />)
    expect(en.querySelector('.callout .sr-only')?.textContent).toBe('Error: ')
  })

  it('localizes the chart data-table headers (ja)', () => {
    localStorage.setItem('nene-locale', 'ja')
    const doc = JSON.stringify([
      {
        id: 'k',
        type: 'chart',
        data: {
          chartType: 'bar',
          title: 'M',
          series: [
            { label: 'a', value: 1 },
            { label: 'b', value: 2 },
          ],
          summary: 's',
        },
      },
    ])

    const { container } = renderWithProviders(<BlocksRenderer documentJson={doc} />)
    const headers = Array.from(container.querySelectorAll('.chart__table thead th')).map(
      (th) => th.textContent,
    )
    expect(headers).toEqual(['ラベル', '値'])
  })

  describe('contact-form (#1030)', () => {
    const DOC = JSON.stringify([
      { id: 'blk1', type: 'contact-form', data: { formKey: 'ayane-contact', variant: 'inline' } },
    ])

    function seedBootstrap(contactForms: unknown): void {
      const script = document.createElement('script')
      script.id = 'nene-records-public-record-bootstrap'
      script.type = 'application/json'
      script.textContent = JSON.stringify({ contactForms })
      document.head.appendChild(script)
    }

    afterEach(() => {
      document.getElementById('nene-records-public-record-bootstrap')?.remove()
    })

    it('renders the form from the SSR bootstrap without fetching again', () => {
      // hub pin 2: the "no extra round trip" claim is a check, not a comment.
      const fetchSpy = vi.spyOn(globalThis, 'fetch')
      seedBootstrap({
        'ayane-contact': {
          formKey: 'ayane-contact',
          submitPath: '/api/v1/public/contact-submissions',
          consentRequired: false,
          consentLabel: null,
          submitLabel: null,
          fields: [
            { key: 'email', label: 'Email', type: 'email', required: true, options: [] },
            { key: 'message', label: 'Message', type: 'textarea', required: false, options: [] },
          ],
        },
      })

      const { container } = renderWithProviders(<BlocksRenderer documentJson={DOC} />)

      const form = container.querySelector('form.block--contact-form')
      expect(form?.getAttribute('action')).toBe('/api/v1/public/contact-submissions')
      expect(form?.getAttribute('method')).toBe('post')
      expect(container.querySelector('input[type="email"][name="email"]')).not.toBeNull()
      expect(container.querySelector('textarea[name="message"]')).not.toBeNull()
      expect(fetchSpy).not.toHaveBeenCalled()

      fetchSpy.mockRestore()
    })

    it('never exposes a credential or the issuing product URL in the markup', () => {
      seedBootstrap({
        'ayane-contact': {
          formKey: 'ayane-contact',
          submitPath: '/api/v1/public/contact-submissions',
          consentRequired: true,
          consentLabel: '同意します',
          submitLabel: '送信',
          fields: [],
        },
      })

      const { container } = renderWithProviders(<BlocksRenderer documentJson={DOC} />)
      const html = container.innerHTML

      expect(html).not.toContain('Bearer')
      expect(html).not.toContain('Authorization')
      expect(html).not.toContain('https://')
      expect(container.querySelector('input[name="consent"]')).not.toBeNull()
      expect(html).toContain('送信')
    })

    it('falls back to a visible notice when the page carries no schema for the key', () => {
      // Mirrors the server's fail-visible behaviour rather than rendering an empty region.
      const { container } = renderWithProviders(<BlocksRenderer documentJson={DOC} />)

      expect(container.querySelector('.contact-form--unavailable')).not.toBeNull()
      expect(container.querySelector('form')).toBeNull()
    })
  })
})

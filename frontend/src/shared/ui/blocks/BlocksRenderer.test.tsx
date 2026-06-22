import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/react'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { BlocksRenderer } from './BlocksRenderer'

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
})

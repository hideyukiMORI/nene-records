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
})

import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { WebhookForm } from './WebhookForm'

afterEach(cleanup)

const baseProps = {
  isSubmitting: false,
  serverErrorTitle: null,
  submitLabel: 'Save',
  onSubmit: vi.fn(() => Promise.resolve()),
}

describe('WebhookForm', () => {
  it('associates each label with its input (htmlFor/id wired via useId)', () => {
    renderWithProviders(<WebhookForm {...baseProps} />)
    expect(screen.getByLabelText('Endpoint URL').tagName).toBe('INPUT')
    expect(screen.getByLabelText('Entity type ID (optional)').tagName).toBe('INPUT')
    expect(screen.getByLabelText('Signing secret (optional)').tagName).toBe('INPUT')
  })

  it('generates unique field ids per instance (no fixed-id collision)', () => {
    renderWithProviders(
      <>
        <WebhookForm {...baseProps} />
        <WebhookForm {...baseProps} />
      </>,
    )
    const urls = screen.getAllByLabelText('Endpoint URL')
    expect(urls).toHaveLength(2)
    expect(urls[0]?.id).toBeTruthy()
    expect(urls[0]?.id).not.toBe(urls[1]?.id)
  })
})

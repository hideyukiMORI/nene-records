import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { NotificationChannelForm } from './NotificationChannelForm'

afterEach(cleanup)

const baseProps = {
  isSubmitting: false,
  serverErrorTitle: null,
  submitLabel: 'Save',
  onSubmit: vi.fn(() => Promise.resolve()),
  onCancel: vi.fn(),
}

describe('NotificationChannelForm', () => {
  it('associates the enabled checkbox with its label (useId)', () => {
    renderWithProviders(<NotificationChannelForm {...baseProps} />)
    const checkbox = screen.getByLabelText('Enabled')
    expect(checkbox).toHaveProperty('type', 'checkbox')
  })

  it('generates a unique enabled-checkbox id per instance', () => {
    renderWithProviders(
      <>
        <NotificationChannelForm {...baseProps} />
        <NotificationChannelForm {...baseProps} />
      </>,
    )
    const boxes = screen.getAllByLabelText('Enabled')
    expect(boxes).toHaveLength(2)
    expect(boxes[0]?.id).toBeTruthy()
    expect(boxes[0]?.id).not.toBe(boxes[1]?.id)
  })
})

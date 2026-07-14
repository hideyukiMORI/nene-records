import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { NotificationChannelForm } from './NotificationChannelForm'
import type {
  NotificationChannel,
  UpdateNotificationChannelInput,
} from '@/entities/notification-channel'

afterEach(cleanup)

const baseProps = {
  isSubmitting: false,
  serverErrorTitle: null,
  submitLabel: 'Save',
  onSubmit: vi.fn(() => Promise.resolve()),
  onCancel: vi.fn(),
}

// A Slack channel whose webhook_url is already stored: the read payload carries
// only the `has_webhook_url` flag, never the secret itself (#845).
const slackWithConfiguredSecret: NotificationChannel = {
  id: 1,
  channelType: 'slack',
  label: 'Slack Alerts',
  isEnabled: true,
  config: { has_webhook_url: true },
  createdAt: '2026-07-01T00:00:00Z',
  updatedAt: '2026-07-01T00:00:00Z',
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

  it('never prefills a write-only secret and marks it password + configured', () => {
    renderWithProviders(
      <NotificationChannelForm {...baseProps} defaultValues={slackWithConfiguredSecret} />,
    )
    const input = screen.getByPlaceholderText('https://hooks.slack.com/services/...')
    expect(input).toHaveProperty('type', 'password')
    expect(input).toHaveProperty('value', '')
    // Hint reflects that a secret is already configured.
    expect(screen.getByText(/already configured/i)).toBeTruthy()
  })

  it('submits without re-entering the secret and omits it from the payload', async () => {
    const onSubmit = vi.fn((input: UpdateNotificationChannelInput) => {
      void input
      return Promise.resolve()
    })
    renderWithProviders(
      <NotificationChannelForm
        {...baseProps}
        onSubmit={onSubmit}
        defaultValues={slackWithConfiguredSecret}
      />,
    )

    await userEvent.click(screen.getByRole('button', { name: 'Save' }))

    expect(onSubmit).toHaveBeenCalledTimes(1)
    const payload = onSubmit.mock.calls[0]?.[0]
    // Blank secret is not sent (backend keeps the stored one); no has_* echo.
    expect(payload?.config).not.toHaveProperty('webhook_url')
    expect(payload?.config).not.toHaveProperty('has_webhook_url')
  })
})

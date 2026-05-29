import { useState } from 'react'
import type { CreateWebhookInput, WebhookEvent } from '@/entities/webhook'
import { WEBHOOK_EVENTS } from '@/entities/webhook'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

export interface WebhookFormProps {
  defaultValues?: Partial<CreateWebhookInput>
  isSubmitting: boolean
  serverErrorTitle: string | null
  submitLabel: string
  onSubmit: (input: CreateWebhookInput) => Promise<void>
  onCancel?: () => void
}

export function WebhookForm({
  defaultValues,
  isSubmitting,
  serverErrorTitle,
  submitLabel,
  onSubmit,
  onCancel,
}: WebhookFormProps) {
  const { t } = useTranslation()
  const [url, setUrl] = useState(defaultValues?.url ?? '')
  const [events, setEvents] = useState<WebhookEvent[]>(defaultValues?.events ?? ['entity.created'])
  const [entityTypeId, setEntityTypeId] = useState<string>(
    defaultValues?.entityTypeId != null ? String(defaultValues.entityTypeId) : '',
  )
  const [secret, setSecret] = useState(defaultValues?.secret ?? '')
  const [isActive, setIsActive] = useState(defaultValues?.isActive ?? true)

  const toggleEvent = (event: WebhookEvent) => {
    setEvents((prev) => (prev.includes(event) ? prev.filter((e) => e !== event) : [...prev, event]))
  }

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault()
    await onSubmit({
      url,
      events,
      entityTypeId: entityTypeId !== '' ? parseInt(entityTypeId, 10) : null,
      secret: secret !== '' ? secret : null,
      isActive,
    })
  }

  return (
    <form
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(e) => void handleSubmit(e)}
    >
      <Stack gap="md">
        {/* URL */}
        <div className="flex flex-col gap-stack-xs">
          <label htmlFor="wh-url" className="font-sans text-body font-medium text-text-primary">
            {t('admin.webhooks.form.urlLabel')}
          </label>
          <input
            id="wh-url"
            type="url"
            value={url}
            disabled={isSubmitting}
            required
            placeholder="https://example.com/hook"
            onChange={(e) => {
              setUrl(e.target.value)
            }}
            className="rounded-md border border-border bg-surface px-3 py-2 font-sans text-body text-text-primary placeholder:text-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
          />
        </div>

        {/* Events */}
        <div className="flex flex-col gap-stack-xs">
          <span className="font-sans text-body font-medium text-text-primary">
            {t('admin.webhooks.form.eventsLabel')}
          </span>
          <div className="flex flex-wrap gap-3">
            {WEBHOOK_EVENTS.map((event) => (
              <label
                key={event}
                className="flex cursor-pointer items-center gap-2 font-sans text-body text-text-primary"
              >
                <input
                  type="checkbox"
                  disabled={isSubmitting}
                  checked={events.includes(event)}
                  onChange={() => {
                    toggleEvent(event)
                  }}
                  className="size-4 rounded border border-border"
                />
                <code className="rounded bg-surface px-1.5 py-0.5 text-caption">{event}</code>
              </label>
            ))}
          </div>
        </div>

        {/* Entity type ID (optional) */}
        <div className="flex flex-col gap-stack-xs">
          <label
            htmlFor="wh-entity-type"
            className="font-sans text-body font-medium text-text-primary"
          >
            {t('admin.webhooks.form.entityTypeIdLabel')}
          </label>
          <input
            id="wh-entity-type"
            type="number"
            value={entityTypeId}
            disabled={isSubmitting}
            min={1}
            placeholder={t('admin.webhooks.form.entityTypeIdPlaceholder')}
            onChange={(e) => {
              setEntityTypeId(e.target.value)
            }}
            className="rounded-md border border-border bg-surface px-3 py-2 font-sans text-body text-text-primary placeholder:text-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
          />
        </div>

        {/* Secret (optional) */}
        <div className="flex flex-col gap-stack-xs">
          <label htmlFor="wh-secret" className="font-sans text-body font-medium text-text-primary">
            {t('admin.webhooks.form.secretLabel')}
          </label>
          <input
            id="wh-secret"
            type="text"
            value={secret}
            disabled={isSubmitting}
            placeholder={t('admin.webhooks.form.secretPlaceholder')}
            onChange={(e) => {
              setSecret(e.target.value)
            }}
            className="rounded-md border border-border bg-surface px-3 py-2 font-sans text-body text-text-primary placeholder:text-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
          />
        </div>

        {/* Is active */}
        <label className="flex cursor-pointer items-center gap-2 font-sans text-body text-text-primary">
          <input
            type="checkbox"
            checked={isActive}
            disabled={isSubmitting}
            onChange={(e) => {
              setIsActive(e.target.checked)
            }}
            className="size-4 rounded border border-border"
          />
          {t('admin.webhooks.form.isActiveLabel')}
        </label>

        {serverErrorTitle !== null && <Text muted>{serverErrorTitle}</Text>}

        <div className="flex gap-2">
          <Button type="submit" disabled={isSubmitting || events.length === 0}>
            {isSubmitting ? t('common.actions.saving') : submitLabel}
          </Button>
          {onCancel !== undefined && (
            <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
              {t('common.actions.cancel')}
            </Button>
          )}
        </div>
      </Stack>
    </form>
  )
}

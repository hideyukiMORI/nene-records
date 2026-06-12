import { useState } from 'react'
import type {
  CreateNotificationChannelInput,
  NotificationChannel,
  NotificationChannelType,
  UpdateNotificationChannelInput,
} from '@/entities/notification-channel'
import { NOTIFICATION_CHANNEL_TYPES } from '@/entities/notification-channel'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Stack, Text } from '@/shared/ui'

type NotificationChannelFormProps =
  | {
      defaultValues?: undefined
      isSubmitting: boolean
      serverErrorTitle: string | null
      submitLabel: string
      onSubmit: (input: CreateNotificationChannelInput) => Promise<void>
      onCancel: () => void
    }
  | {
      defaultValues: NotificationChannel
      isSubmitting: boolean
      serverErrorTitle: string | null
      submitLabel: string
      onSubmit: (input: UpdateNotificationChannelInput) => Promise<void>
      onCancel: () => void
    }

function getConfigFields(channelType: NotificationChannelType): Array<{
  key: string
  label: string
  placeholder: string
  required: boolean
  multiline?: boolean
}> {
  switch (channelType) {
    case 'email':
      return [
        {
          key: 'to_address',
          label: 'To address',
          placeholder: 'recipient@example.com',
          required: true,
        },
      ]
    case 'slack':
      return [
        {
          key: 'webhook_url',
          label: 'Webhook URL',
          placeholder: 'https://hooks.slack.com/services/...',
          required: true,
        },
      ]
    case 'discord':
      return [
        {
          key: 'webhook_url',
          label: 'Webhook URL',
          placeholder: 'https://discord.com/api/webhooks/...',
          required: true,
        },
      ]
    case 'chatwork':
      return [
        { key: 'api_token', label: 'API token', placeholder: 'ChatWork API token', required: true },
        { key: 'room_id', label: 'Room ID', placeholder: '123456', required: true },
      ]
    case 'webhook':
      return [
        {
          key: 'url',
          label: 'Endpoint URL',
          placeholder: 'https://example.com/hook',
          required: true,
        },
        {
          key: 'headers_json',
          label: 'Extra headers (JSON)',
          placeholder: '{"X-Token": "secret"}',
          required: false,
          multiline: true,
        },
      ]
  }
}

export function NotificationChannelForm({
  defaultValues,
  isSubmitting,
  serverErrorTitle,
  submitLabel,
  onSubmit,
  onCancel,
}: NotificationChannelFormProps) {
  const { t } = useTranslation()
  const isEdit = defaultValues !== undefined

  const [channelType, setChannelType] = useState<NotificationChannelType>(
    defaultValues?.channelType ?? 'email',
  )
  const [label, setLabel] = useState(defaultValues?.label ?? '')
  const [isEnabled, setIsEnabled] = useState(defaultValues?.isEnabled ?? true)
  const [config, setConfig] = useState<Record<string, string>>(() => {
    if (defaultValues?.config == null) return {}
    return Object.fromEntries(
      Object.entries(defaultValues.config).map(([k, v]) => [
        k,
        typeof v === 'string'
          ? v
          : typeof v === 'number' || typeof v === 'boolean'
            ? String(v)
            : '',
      ]),
    )
  })
  const [errors, setErrors] = useState<Record<string, string>>({})

  function setConfigField(key: string, value: string) {
    setConfig((prev) => ({ ...prev, [key]: value }))
  }

  function validate(): boolean {
    const next: Record<string, string> = {}
    if (label.trim() === '') {
      next['label'] = t('admin.notifications.form.labelRequired')
    }
    for (const field of getConfigFields(channelType)) {
      if (field.required && !config[field.key]?.trim()) {
        next[field.key] = t('admin.notifications.form.fieldRequired', { field: field.label })
      }
    }
    setErrors(next)
    return Object.keys(next).length === 0
  }

  async function handleSubmit(e: React.SyntheticEvent) {
    e.preventDefault()
    if (!validate()) return

    const cleanConfig: Record<string, string> = {}
    for (const field of getConfigFields(channelType)) {
      const v = config[field.key]?.trim() ?? ''
      if (v !== '') cleanConfig[field.key] = v
    }

    if (isEdit) {
      await onSubmit({
        label: label.trim(),
        isEnabled,
        config: cleanConfig,
      })
    } else {
      await onSubmit({
        channelType,
        label: label.trim(),
        isEnabled,
        config: cleanConfig,
      })
    }
  }

  const configFields = getConfigFields(channelType)

  return (
    <Card
      as="form"
      onSubmit={(e) => {
        void handleSubmit(e)
      }}
    >
      <Stack gap="sm">
        {serverErrorTitle !== null && (
          <Text className="text-error text-sm">{serverErrorTitle}</Text>
        )}

        {!isEdit && (
          <div>
            <label className="mb-1 block font-sans text-sm font-medium text-text">
              {t('admin.notifications.form.channelTypeLabel')}
            </label>
            <select
              value={channelType}
              onChange={(e) => {
                setChannelType(e.target.value as NotificationChannelType)
                setConfig({})
                setErrors({})
              }}
              className="w-full rounded-md border border-border bg-surface px-3 py-2 font-sans text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
            >
              {NOTIFICATION_CHANNEL_TYPES.map((type) => (
                <option key={type} value={type}>
                  {t(`admin.notifications.channelType.${type}`)}
                </option>
              ))}
            </select>
          </div>
        )}

        <div>
          <label className="mb-1 block font-sans text-sm font-medium text-text">
            {t('admin.notifications.form.labelLabel')}
          </label>
          <input
            type="text"
            value={label}
            onChange={(e) => {
              setLabel(e.target.value)
            }}
            placeholder={t('admin.notifications.form.labelPlaceholder')}
            className="w-full rounded-md border border-border bg-surface px-3 py-2 font-sans text-sm text-text placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent"
          />
          {errors['label'] !== undefined && (
            <p className="mt-1 font-sans text-xs text-error">{errors['label']}</p>
          )}
        </div>

        {configFields.map((field) => (
          <div key={field.key}>
            <label className="mb-1 block font-sans text-sm font-medium text-text">
              {field.label}
              {field.required && <span className="ml-1 text-error">*</span>}
            </label>
            {field.multiline === true ? (
              <textarea
                value={config[field.key] ?? ''}
                onChange={(e) => {
                  setConfigField(field.key, e.target.value)
                }}
                placeholder={field.placeholder}
                rows={3}
                className="w-full rounded-md border border-border bg-surface px-3 py-2 font-mono text-sm text-text placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent"
              />
            ) : (
              <input
                type={field.key === 'api_token' ? 'password' : 'text'}
                value={config[field.key] ?? ''}
                onChange={(e) => {
                  setConfigField(field.key, e.target.value)
                }}
                placeholder={field.placeholder}
                className="w-full rounded-md border border-border bg-surface px-3 py-2 font-sans text-sm text-text placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-accent"
              />
            )}
            {errors[field.key] !== undefined && (
              <p className="mt-1 font-sans text-xs text-error">{errors[field.key]}</p>
            )}
          </div>
        ))}

        <div className="flex items-center gap-2">
          <input
            id="is-enabled"
            type="checkbox"
            checked={isEnabled}
            onChange={(e) => {
              setIsEnabled(e.target.checked)
            }}
            className="h-4 w-4 rounded border-border text-accent"
          />
          <label htmlFor="is-enabled" className="font-sans text-sm text-text">
            {t('admin.notifications.form.isEnabledLabel')}
          </label>
        </div>

        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="secondary" size="sm" onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
          <Button type="submit" size="sm" disabled={isSubmitting}>
            {isSubmitting ? t('common.actions.saving') : submitLabel}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}

import type { ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Stack, Text } from '@/shared/ui'
import type { HeaderConfigPageState } from '../hooks/useHeaderConfigPage'

function Row({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="flex items-center justify-between gap-inline-md">
      <span className="font-chrome text-caption font-semibold text-text-primary">{label}</span>
      {children}
    </label>
  )
}

function TextInput({
  value,
  onChange,
  placeholder,
}: {
  value: string
  onChange: (value: string) => void
  placeholder?: string
}) {
  return (
    <input
      type="text"
      className="w-56 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
      value={value}
      placeholder={placeholder}
      onChange={(event) => {
        onChange(event.target.value)
      }}
    />
  )
}

function Checkbox({
  checked,
  onChange,
  label,
}: {
  checked: boolean
  onChange: (checked: boolean) => void
  label: string
}) {
  return (
    <label className="flex items-center gap-inline-sm">
      <input
        type="checkbox"
        className="h-4 w-4 accent-accent"
        checked={checked}
        onChange={(event) => {
          onChange(event.target.checked)
        }}
      />
      <span className="font-chrome text-caption font-semibold text-text-primary">{label}</span>
    </label>
  )
}

/**
 * Admin editor for public-site header content (#419 Phase C): the Top bar
 * (phone / email / free text) and the CTA button (label + URL). Saves to the
 * `header_config` setting; the public shell renders the row + button.
 */
export function HeaderContentView({
  draft,
  setTopbar,
  setCta,
  save,
  isSaving,
  isDirty,
  isLoading,
}: HeaderConfigPageState) {
  const { t } = useTranslation()
  const disabled = isLoading || isSaving

  return (
    <Card padding="md">
      <Stack gap="md">
        <Text muted variant="caption">
          {t('admin.headerContent.intro')}
        </Text>

        <Stack gap="sm">
          <Checkbox
            checked={draft.topbar.enabled}
            label={t('admin.headerContent.topbarEnabled')}
            onChange={(enabled) => {
              setTopbar({ enabled })
            }}
          />
          <Row label={t('admin.headerContent.phone')}>
            <TextInput
              value={draft.topbar.phone}
              placeholder="03-1234-5678"
              onChange={(phone) => {
                setTopbar({ phone })
              }}
            />
          </Row>
          <Row label={t('admin.headerContent.email')}>
            <TextInput
              value={draft.topbar.email}
              placeholder="info@example.com"
              onChange={(email) => {
                setTopbar({ email })
              }}
            />
          </Row>
          <Row label={t('admin.headerContent.infoText')}>
            <TextInput
              value={draft.topbar.infoText}
              onChange={(infoText) => {
                setTopbar({ infoText })
              }}
            />
          </Row>
        </Stack>

        <Stack gap="sm">
          <Checkbox
            checked={draft.cta.enabled}
            label={t('admin.headerContent.ctaEnabled')}
            onChange={(enabled) => {
              setCta({ enabled })
            }}
          />
          <Row label={t('admin.headerContent.ctaLabel')}>
            <TextInput
              value={draft.cta.label}
              onChange={(label) => {
                setCta({ label })
              }}
            />
          </Row>
          <Row label={t('admin.headerContent.ctaUrl')}>
            <TextInput
              value={draft.cta.url}
              placeholder="/contact"
              onChange={(url) => {
                setCta({ url })
              }}
            />
          </Row>
        </Stack>

        <div>
          <Button onClick={save} disabled={disabled || !isDirty}>
            {isSaving ? t('admin.headerContent.saving') : t('admin.headerContent.save')}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}

import { useState } from 'react'
import type { FieldDef } from '@/entities/field-def'
import type { Entity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text, useToast } from '@/shared/ui'
import { EntityTextFieldsForm } from './EntityTextFieldsForm'

export interface EditEntityTextFieldsViewProps {
  entity: Entity | null
  textFieldDefs: FieldDef[]
  initialValues: Record<string, string>
  selectedLocale: string | null
  availableLocales: string[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isSaving: boolean
  saveErrorTitle: string | null
  onLocaleChange: (locale: string | null) => void
  onRetry: () => void
  onSave: (values: Record<string, string>) => Promise<void>
}

export function EditEntityTextFieldsView({
  entity,
  textFieldDefs,
  initialValues,
  selectedLocale,
  availableLocales,
  isLoading,
  isError,
  errorTitle,
  isSaving,
  saveErrorTitle,
  onLocaleChange,
  onRetry,
  onSave,
}: EditEntityTextFieldsViewProps) {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const [customLocaleInput, setCustomLocaleInput] = useState('')

  const handleSave = async (values: Record<string, string>) => {
    try {
      await onSave(values)
      showToast(t('admin.entityRecord.textFields.saved'), 'success')
    } catch {
      // エラーは saveErrorTitle 経由で EntityTextFieldsForm 内に表示される
    }
  }

  if (isLoading) {
    return <Text muted>{t('admin.entityRecord.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('admin.entityRecord.error')}</Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={onRetry}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
    )
  }

  if (entity === null) {
    return <Text muted>{t('admin.entityRecord.notFound')}</Text>
  }

  const handleCustomLocaleSwitch = () => {
    const trimmed = customLocaleInput.trim()
    if (trimmed !== '') {
      onLocaleChange(trimmed)
      setCustomLocaleInput('')
    }
  }

  return (
    <Stack gap="lg">
      <Text as="p" muted>
        {t('admin.entityRecord.id', { id: entity.id })}
      </Text>

      {/* ── Locale Switcher ── */}
      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('admin.entityRecord.locale.label')}
        </Text>
        <div className="flex flex-wrap gap-inline-sm">
          <Button
            variant={selectedLocale === null ? 'primary' : 'secondary'}
            size="sm"
            aria-pressed={selectedLocale === null}
            onClick={() => {
              onLocaleChange(null)
            }}
          >
            {t('admin.entityRecord.locale.default')}
          </Button>
          {availableLocales.map((locale) => (
            <Button
              key={locale}
              variant={selectedLocale === locale ? 'primary' : 'secondary'}
              size="sm"
              aria-pressed={selectedLocale === locale}
              onClick={() => {
                onLocaleChange(locale)
              }}
            >
              {locale}
            </Button>
          ))}
        </div>
        <div className="flex items-center gap-inline-sm">
          <input
            type="text"
            value={customLocaleInput}
            onChange={(e) => {
              setCustomLocaleInput(e.target.value)
            }}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                handleCustomLocaleSwitch()
              }
            }}
            placeholder={t('admin.entityRecord.locale.inputPlaceholder')}
            aria-label={t('admin.entityRecord.locale.inputPlaceholder')}
            className="w-32 rounded-md border border-border bg-surface-raised px-inline-sm py-stack-xs font-sans text-caption text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus"
          />
          <Button
            variant="secondary"
            size="sm"
            onClick={handleCustomLocaleSwitch}
            disabled={customLocaleInput.trim() === ''}
          >
            {t('admin.entityRecord.locale.switch')}
          </Button>
        </div>
        {selectedLocale !== null ? (
          <Text muted>{t('admin.entityRecord.locale.editing', { locale: selectedLocale })}</Text>
        ) : (
          <Text muted>{t('admin.entityRecord.locale.editingDefault')}</Text>
        )}
      </Stack>

      <EntityTextFieldsForm
        key={`${selectedLocale ?? 'default'}-${JSON.stringify(initialValues)}`}
        fieldDefs={textFieldDefs}
        defaultValues={initialValues}
        isSubmitting={isSaving}
        serverErrorTitle={saveErrorTitle}
        onSubmit={handleSave}
      />
    </Stack>
  )
}

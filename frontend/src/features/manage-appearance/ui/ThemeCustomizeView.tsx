import type { ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import {
  DENSITY_OPTIONS,
  FONT_OPTIONS,
  FONT_SIZE_OPTIONS,
  GUTTER_OPTIONS,
  RADIUS_OPTIONS,
  TYPE_SCALE_OPTIONS,
  WIDTH_OPTIONS,
  type KnobOption,
} from '@/shared/lib/theme-customization'
import { Button, Card, Stack, Text } from '@/shared/ui'
import type { ThemeCustomizePageState } from '../hooks/useThemeCustomizePage'

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="flex items-center justify-between gap-inline-md">
      <span className="font-chrome text-caption font-semibold text-text-primary">{label}</span>
      {children}
    </label>
  )
}

function Select({
  value,
  options,
  placeholder,
  onChange,
}: {
  value: string | undefined
  options: readonly KnobOption[]
  placeholder: string
  onChange: (value: string | undefined) => void
}) {
  return (
    <select
      className="rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
      value={value ?? ''}
      onChange={(event) => {
        onChange(event.target.value === '' ? undefined : event.target.value)
      }}
    >
      <option value="">{placeholder}</option>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  )
}

/**
 * Admin customizer for the active public theme — a small set of knobs
 * (accent / body font / width / gutter / radius) stored per theme. Applied to
 * the public site as inline CSS custom properties.
 */
export function ThemeCustomizeView({
  themeId,
  draft,
  setKnob,
  save,
  reset,
  isLoading,
  isSaving,
  isDirty,
}: ThemeCustomizePageState) {
  const { t } = useTranslation()
  const disabled = isLoading || isSaving
  const themePlaceholder = t('admin.themeCustomize.themeDefault')

  return (
    <Card padding="none" className="p-stack-md">
      <Stack gap="md">
        <Text muted variant="caption">
          {t('admin.themeCustomize.intro', { theme: themeId })}
        </Text>

        <Stack gap="sm">
          <Field label={t('admin.themeCustomize.accent')}>
            <input
              type="color"
              className="h-8 w-14 cursor-pointer rounded-sm border border-border bg-surface"
              value={draft.accent ?? '#000000'}
              disabled={disabled}
              onChange={(event) => {
                setKnob('accent', event.target.value)
              }}
              aria-label={t('admin.themeCustomize.accent')}
            />
          </Field>
          <Field label={t('admin.themeCustomize.font')}>
            <Select
              value={draft.fontBody}
              options={FONT_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('fontBody', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.width')}>
            <Select
              value={draft.contentWidth}
              options={WIDTH_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('contentWidth', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.gutter')}>
            <Select
              value={draft.gutter}
              options={GUTTER_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('gutter', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.radius')}>
            <Select
              value={draft.radius}
              options={RADIUS_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('radius', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.fontSize')}>
            <Select
              value={draft.fontSize}
              options={FONT_SIZE_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('fontSize', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.typeScale')}>
            <Select
              value={draft.typeScale}
              options={TYPE_SCALE_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('typeScale', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.density')}>
            <Select
              value={draft.density}
              options={DENSITY_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('density', v)
              }}
            />
          </Field>
        </Stack>

        <div className="flex items-center gap-inline-sm">
          <Button onClick={save} disabled={disabled || !isDirty}>
            {isSaving ? t('admin.themeCustomize.saving') : t('admin.themeCustomize.save')}
          </Button>
          <Button variant="ghost" onClick={reset} disabled={disabled}>
            {t('admin.themeCustomize.reset')}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}

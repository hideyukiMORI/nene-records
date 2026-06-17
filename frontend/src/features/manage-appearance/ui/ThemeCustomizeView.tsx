import type { ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import {
  DENSITY_OPTIONS,
  FLAG_DEFS,
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

function ColorInput({
  value,
  onChange,
  ariaLabel,
  disabled,
}: {
  value: string | undefined
  onChange: (value: string) => void
  ariaLabel: string
  disabled: boolean
}) {
  return (
    <input
      type="color"
      className="h-8 w-14 cursor-pointer rounded-sm border border-border bg-surface"
      value={value ?? '#888888'}
      disabled={disabled}
      onChange={(event) => {
        onChange(event.target.value)
      }}
      aria-label={ariaLabel}
    />
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
            <ColorInput
              value={draft.accent}
              disabled={disabled}
              ariaLabel={t('admin.themeCustomize.accent')}
              onChange={(v) => {
                setKnob('accent', v)
              }}
            />
          </Field>
          <div className="flex items-center justify-between gap-inline-md">
            <span className="font-chrome text-caption font-semibold text-text-primary">
              {t('admin.themeCustomize.surface')}
            </span>
            <span className="flex items-center gap-inline-sm">
              <ColorInput
                value={draft.surface?.light}
                disabled={disabled}
                ariaLabel={`${t('admin.themeCustomize.surface')} (light)`}
                onChange={(v) => {
                  setKnob('surface', { ...draft.surface, light: v })
                }}
              />
              <ColorInput
                value={draft.surface?.dark}
                disabled={disabled}
                ariaLabel={`${t('admin.themeCustomize.surface')} (dark)`}
                onChange={(v) => {
                  setKnob('surface', { ...draft.surface, dark: v })
                }}
              />
            </span>
          </div>
          <div className="flex items-center justify-between gap-inline-md">
            <span className="font-chrome text-caption font-semibold text-text-primary">
              {t('admin.themeCustomize.text')}
            </span>
            <span className="flex items-center gap-inline-sm">
              <ColorInput
                value={draft.text?.light}
                disabled={disabled}
                ariaLabel={`${t('admin.themeCustomize.text')} (light)`}
                onChange={(v) => {
                  setKnob('text', { ...draft.text, light: v })
                }}
              />
              <ColorInput
                value={draft.text?.dark}
                disabled={disabled}
                ariaLabel={`${t('admin.themeCustomize.text')} (dark)`}
                onChange={(v) => {
                  setKnob('text', { ...draft.text, dark: v })
                }}
              />
            </span>
          </div>
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
          <Field label={t('admin.themeCustomize.feedLayout')}>
            <Select
              value={draft.flags?.feedLayout}
              options={FLAG_DEFS.feedLayout.options}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('flags', { ...draft.flags, feedLayout: v })
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.cardStyle')}>
            <Select
              value={draft.flags?.cardStyle}
              options={FLAG_DEFS.cardStyle.options}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('flags', { ...draft.flags, cardStyle: v })
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.media')}>
            <Select
              value={draft.flags?.media}
              options={FLAG_DEFS.media.options}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('flags', { ...draft.flags, media: v })
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.hero')}>
            <Select
              value={draft.flags?.hero}
              options={FLAG_DEFS.hero.options}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('flags', { ...draft.flags, hero: v })
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.sectionRule')}>
            <Select
              value={draft.flags?.sectionRule}
              options={FLAG_DEFS.sectionRule.options}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('flags', { ...draft.flags, sectionRule: v })
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.eyebrow')}>
            <Select
              value={draft.flags?.eyebrow}
              options={FLAG_DEFS.eyebrow.options}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('flags', { ...draft.flags, eyebrow: v })
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

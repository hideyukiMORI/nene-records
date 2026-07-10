import { type ReactNode, useState } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import {
  BRAND_FONT_OPTIONS,
  BRAND_SIZE_OPTIONS,
  DENSITY_OPTIONS,
  DISPLAY_FONT_OPTIONS,
  FLAG_DEFS,
  FONT_OPTIONS,
  FONT_SIZE_OPTIONS,
  GUTTER_OPTIONS,
  MENU_SIZE_OPTIONS,
  MONO_FONT_OPTIONS,
  RADIUS_OPTIONS,
  TYPE_SCALE_OPTIONS,
  WIDTH_OPTIONS,
  type KnobOption,
  type ThemeFlags,
  type ThemeImages,
} from '@/shared/lib/theme-customization'
import { Button, Card, ConfirmDialog, Input, Stack, Text, Textarea } from '@/shared/ui'
import type { ThemeCustomizePageState } from '../../hooks/useThemeCustomizePage'
import { ThemeImageField } from '../ThemeImageField'
import { TokenOverridesEditor } from '../TokenOverridesEditor'
import { Segmented } from './Segmented'

/* ── shared building blocks ─────────────────────────────────────────────── */

/** Save-scope pill: which setting a control writes to (the redesign's core idea). */
export function ScopeTag({
  kind,
  labelKey,
}: {
  kind: 'overrides' | 'independent' | 'instant'
  labelKey: MessageKey
}) {
  const { t } = useTranslation()
  const modifier = kind === 'overrides' ? '' : ` ${kind}`
  return <span className={`ws-scope-tag${modifier}`}>{t(labelKey)}</span>
}

/** Per-panel banner naming the save scope, mirroring the prototype's scope-note. */
export function ScopeNote({
  kind,
  tagKey,
  noteKey,
}: {
  kind: 'overrides' | 'independent' | 'instant'
  tagKey: MessageKey
  noteKey: MessageKey
}) {
  const { t } = useTranslation()
  return (
    <div className="ws-scope-note">
      <ScopeTag kind={kind} labelKey={tagKey} />
      <span>{t(noteKey)}</span>
    </div>
  )
}

export function PanelHead({ title, desc }: { title: string; desc: string }) {
  return (
    <Stack gap="xs" className="mb-4">
      <Text as="h2" variant="heading-sm">
        {title}
      </Text>
      <Text muted variant="caption">
        {desc}
      </Text>
    </Stack>
  )
}

export function FieldRow({
  label,
  hint,
  children,
}: {
  label: string
  hint?: string | undefined
  children: ReactNode
}) {
  return (
    <div className="ws-field">
      <span className="ws-flabel">
        {label}
        {hint !== undefined ? <span className="ws-fhint">{hint}</span> : null}
      </span>
      <span className="ws-fctl">{children}</span>
    </div>
  )
}

export function WsCard({
  title,
  sub,
  action,
  children,
}: {
  title: string
  sub?: string | undefined
  action?: ReactNode
  children: ReactNode
}) {
  return (
    <Card padding="none" className="mb-4 p-stack-md">
      <div className="mb-2 flex items-baseline justify-between gap-inline-md">
        <Text as="h3" variant="heading-sm">
          {title}
        </Text>
        {action ??
          (sub !== undefined ? (
            <Text as="span" muted variant="caption">
              {sub}
            </Text>
          ) : null)}
      </div>
      {children}
    </Card>
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
    <span className="inline-flex items-center gap-inline-sm">
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
      <span className="font-mono text-caption uppercase text-text-muted">{value ?? '—'}</span>
    </span>
  )
}

/** The string-preset knobs a segment can bind to (subset of ThemeOverrides). */
type PresetKnob =
  | 'brandSize'
  | 'menuSize'
  | 'contentWidth'
  | 'gutter'
  | 'radius'
  | 'fontSize'
  | 'typeScale'
  | 'density'

/** Knob segment bound to a ThemeOverrides key (string preset knobs). */
function KnobSegment({
  customize,
  knob,
  label,
  options,
  hint,
  wrap,
}: {
  customize: ThemeCustomizePageState
  knob: PresetKnob
  label: string
  options: readonly KnobOption[]
  hint?: string | undefined
  wrap?: boolean
}) {
  const { t } = useTranslation()
  return (
    <FieldRow label={label} hint={hint}>
      <Segmented
        ariaLabel={label}
        value={customize.draft[knob]}
        options={options}
        defaultLabel={t('admin.themeCustomize.themeDefault')}
        disabled={customize.isLoading || customize.isSaving}
        wrap={wrap ?? false}
        onChange={(next) => {
          customize.setKnob(knob, next)
        }}
      />
    </FieldRow>
  )
}

/** Flag segment bound to a ThemeFlags key. */
function FlagSegment({
  customize,
  flag,
  label,
  wrap,
}: {
  customize: ThemeCustomizePageState
  flag: keyof ThemeFlags
  label: string
  wrap?: boolean
}) {
  const { t } = useTranslation()
  return (
    <FieldRow label={label}>
      <Segmented
        ariaLabel={label}
        value={customize.draft.flags?.[flag]}
        options={FLAG_DEFS[flag].options}
        defaultLabel={t('admin.themeCustomize.themeDefault')}
        disabled={customize.isLoading || customize.isSaving}
        wrap={wrap ?? false}
        onChange={(next) => {
          customize.setKnob('flags', { ...customize.draft.flags, [flag]: next })
        }}
      />
    </FieldRow>
  )
}

/** Font select (many options — the one control kept as a dropdown). */
function FontSelect({
  customize,
  knob,
  label,
  options,
}: {
  customize: ThemeCustomizePageState
  knob: 'fontBody' | 'fontDisplay' | 'fontMono' | 'fontBrand'
  label: string
  options: readonly KnobOption[]
}) {
  const { t } = useTranslation()
  return (
    <FieldRow label={label}>
      <select
        className="max-w-64 rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
        value={customize.draft[knob] ?? ''}
        aria-label={label}
        disabled={customize.isLoading || customize.isSaving}
        onChange={(event) => {
          customize.setKnob(knob, event.target.value === '' ? undefined : event.target.value)
        }}
      >
        <option value="">{t('admin.themeCustomize.themeDefault')}</option>
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </FieldRow>
  )
}

/* ── image slot helpers (shared with the brand panel) ───────────────────── */

export interface ImageSlotHelpers {
  thumbUrl: (slot: keyof ThemeImages, mode: 'light' | 'dark') => string | undefined
  setImage: (slot: keyof ThemeImages, mode: 'light' | 'dark', id: number | undefined) => void
}

/* ── panels ─────────────────────────────────────────────────────────────── */

export function BrandPanel({
  customize,
  images,
}: {
  customize: ThemeCustomizePageState
  images: ImageSlotHelpers
}) {
  const { t } = useTranslation()
  const disabled = customize.isLoading || customize.isSaving
  const pickerLabels = {
    select: t('admin.blocks.media.select'),
    change: t('admin.blocks.media.change'),
    remove: t('admin.blocks.media.remove'),
  }
  return (
    <div>
      <PanelHead title={t('admin.themeWs.navBrand')} desc={t('admin.themeWs.descBrand')} />
      <ScopeNote
        kind="overrides"
        tagKey="admin.themeWs.tagOverridesInline"
        noteKey="admin.themeWs.scopeOverridesNote"
      />
      <WsCard title={t('admin.themeWs.cardColors')} sub={t('admin.themeWs.cardColorsSub')}>
        <FieldRow label={t('admin.themeCustomize.accent')} hint={t('admin.themeWs.hintAccent')}>
          <ColorInput
            value={customize.draft.accent}
            disabled={disabled}
            ariaLabel={t('admin.themeCustomize.accent')}
            onChange={(v) => {
              customize.setKnob('accent', v)
            }}
          />
        </FieldRow>
        <FieldRow label={t('admin.themeCustomize.surface')}>
          <ColorInput
            value={customize.draft.surface?.light}
            disabled={disabled}
            ariaLabel={`${t('admin.themeCustomize.surface')} (light)`}
            onChange={(v) => {
              customize.setKnob('surface', { ...customize.draft.surface, light: v })
            }}
          />
          <ColorInput
            value={customize.draft.surface?.dark}
            disabled={disabled}
            ariaLabel={`${t('admin.themeCustomize.surface')} (dark)`}
            onChange={(v) => {
              customize.setKnob('surface', { ...customize.draft.surface, dark: v })
            }}
          />
        </FieldRow>
        <FieldRow label={t('admin.themeCustomize.text')}>
          <ColorInput
            value={customize.draft.text?.light}
            disabled={disabled}
            ariaLabel={`${t('admin.themeCustomize.text')} (light)`}
            onChange={(v) => {
              customize.setKnob('text', { ...customize.draft.text, light: v })
            }}
          />
          <ColorInput
            value={customize.draft.text?.dark}
            disabled={disabled}
            ariaLabel={`${t('admin.themeCustomize.text')} (dark)`}
            onChange={(v) => {
              customize.setKnob('text', { ...customize.draft.text, dark: v })
            }}
          />
        </FieldRow>
      </WsCard>
      <WsCard title={t('admin.themeCustomize.imagesGroup')} sub={t('admin.themeWs.cardImagesSub')}>
        <Stack gap="sm">
          <ThemeImageField
            label={t('admin.themeCustomize.logo')}
            lightLabel="light"
            darkLabel="dark"
            pickerLabels={pickerLabels}
            value={customize.draft.images?.logo}
            urlFor={(mode) => images.thumbUrl('logo', mode)}
            disabled={disabled}
            onChange={(mode, id) => {
              images.setImage('logo', mode, id)
            }}
          />
          <ThemeImageField
            label={t('admin.themeCustomize.heroImage')}
            lightLabel="light"
            darkLabel="dark"
            pickerLabels={pickerLabels}
            value={customize.draft.images?.hero}
            urlFor={(mode) => images.thumbUrl('hero', mode)}
            disabled={disabled}
            onChange={(mode, id) => {
              images.setImage('hero', mode, id)
            }}
          />
          <ThemeImageField
            label={t('admin.themeCustomize.background')}
            lightLabel="light"
            darkLabel="dark"
            pickerLabels={pickerLabels}
            value={customize.draft.images?.background}
            urlFor={(mode) => images.thumbUrl('background', mode)}
            disabled={disabled}
            onChange={(mode, id) => {
              images.setImage('background', mode, id)
            }}
          />
        </Stack>
      </WsCard>
    </div>
  )
}

export function TypographyPanel({ customize }: { customize: ThemeCustomizePageState }) {
  const { t } = useTranslation()
  return (
    <div>
      <PanelHead title={t('admin.themeWs.navType')} desc={t('admin.themeWs.descType')} />
      <ScopeNote
        kind="overrides"
        tagKey="admin.themeWs.tagOverridesInline"
        noteKey="admin.themeWs.scopeOverridesNote"
      />
      <WsCard title={t('admin.themeWs.cardFonts')}>
        <FontSelect
          customize={customize}
          knob="fontBody"
          label={t('admin.themeCustomize.font')}
          options={FONT_OPTIONS}
        />
        <FontSelect
          customize={customize}
          knob="fontDisplay"
          label={t('admin.themeCustomize.fontDisplay')}
          options={DISPLAY_FONT_OPTIONS}
        />
        <FontSelect
          customize={customize}
          knob="fontMono"
          label={t('admin.themeCustomize.fontMono')}
          options={MONO_FONT_OPTIONS}
        />
        <FontSelect
          customize={customize}
          knob="fontBrand"
          label={t('admin.themeCustomize.fontBrand')}
          options={BRAND_FONT_OPTIONS}
        />
      </WsCard>
      <WsCard title={t('admin.themeWs.cardSizes')}>
        <KnobSegment
          customize={customize}
          knob="fontSize"
          label={t('admin.themeCustomize.fontSize')}
          options={FONT_SIZE_OPTIONS}
        />
        <KnobSegment
          customize={customize}
          knob="brandSize"
          label={t('admin.themeCustomize.brandSize')}
          options={BRAND_SIZE_OPTIONS}
        />
        <KnobSegment
          customize={customize}
          knob="menuSize"
          label={t('admin.themeCustomize.menuSize')}
          options={MENU_SIZE_OPTIONS}
        />
      </WsCard>
    </div>
  )
}

export function LayoutPanel({ customize }: { customize: ThemeCustomizePageState }) {
  const { t } = useTranslation()
  return (
    <div>
      <PanelHead title={t('admin.themeWs.navLayout')} desc={t('admin.themeWs.descLayout')} />
      <ScopeNote
        kind="overrides"
        tagKey="admin.themeWs.tagOverridesInline"
        noteKey="admin.themeWs.scopeOverridesNote"
      />
      <WsCard title={t('admin.themeWs.cardPage')}>
        <KnobSegment
          customize={customize}
          knob="contentWidth"
          label={t('admin.themeCustomize.width')}
          options={WIDTH_OPTIONS}
        />
        <KnobSegment
          customize={customize}
          knob="density"
          label={t('admin.themeCustomize.density')}
          options={DENSITY_OPTIONS}
          hint={t('admin.themeWs.hintDensity')}
        />
      </WsCard>
      <WsCard title={t('admin.themeWs.cardFeed')}>
        <FlagSegment
          customize={customize}
          flag="feedLayout"
          label={t('admin.themeCustomize.feedLayout')}
        />
        <FlagSegment
          customize={customize}
          flag="feedColumns"
          label={t('admin.themeCustomize.feedColumns')}
        />
        <FlagSegment
          customize={customize}
          flag="cardStyle"
          label={t('admin.themeCustomize.cardStyle')}
          wrap
        />
      </WsCard>
      <WsCard title={t('admin.themeWs.cardShape')}>
        <KnobSegment
          customize={customize}
          knob="radius"
          label={t('admin.themeCustomize.radius')}
          options={RADIUS_OPTIONS}
        />
        <KnobSegment
          customize={customize}
          knob="gutter"
          label={t('admin.themeCustomize.gutter')}
          options={GUTTER_OPTIONS}
        />
        <KnobSegment
          customize={customize}
          knob="typeScale"
          label={t('admin.themeCustomize.typeScale')}
          options={TYPE_SCALE_OPTIONS}
        />
      </WsCard>
      <WsCard title={t('admin.themeWs.cardStyleFlags')}>
        <FlagSegment
          customize={customize}
          flag="media"
          label={t('admin.themeCustomize.media')}
          wrap
        />
        <FlagSegment
          customize={customize}
          flag="hero"
          label={t('admin.themeCustomize.hero')}
          wrap
        />
        <FlagSegment
          customize={customize}
          flag="sectionRule"
          label={t('admin.themeCustomize.sectionRule')}
        />
        <FlagSegment
          customize={customize}
          flag="eyebrow"
          label={t('admin.themeCustomize.eyebrow')}
        />
      </WsCard>
    </div>
  )
}

/** The header-appearance disclosure (theme_overrides side of the header panel). */
export function HeaderAppearanceDisclosure({ customize }: { customize: ThemeCustomizePageState }) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  return (
    <Card padding="none" className="mb-4">
      <button
        type="button"
        aria-expanded={open}
        className="flex w-full items-center gap-inline-sm p-stack-md text-left font-chrome text-body font-semibold text-text-primary"
        onClick={() => {
          setOpen((prev) => !prev)
        }}
      >
        <span aria-hidden="true">{open ? '▾' : '▸'}</span>
        {t('admin.themeWs.discHeaderLook')}
        <span className="ml-auto">
          <ScopeTag kind="overrides" labelKey="admin.themeWs.tagOverridesInline" />
        </span>
      </button>
      {open ? (
        <div className="border-t border-border p-stack-md pt-stack-sm">
          <Text muted variant="caption" className="mb-2 block">
            {t('admin.themeWs.headerLookHint')}
          </Text>
          <FlagSegment
            customize={customize}
            flag="headerLayout"
            label={t('admin.themeCustomize.headerLayout')}
            wrap
          />
          <FlagSegment
            customize={customize}
            flag="headerNavAlign"
            label={t('admin.themeCustomize.headerNavAlign')}
          />
          <FlagSegment
            customize={customize}
            flag="headerDensity"
            label={t('admin.themeCustomize.headerDensity')}
          />
          <FlagSegment
            customize={customize}
            flag="headerWidth"
            label={t('admin.themeCustomize.headerWidth')}
          />
          <FlagSegment
            customize={customize}
            flag="headerSticky"
            label={t('admin.themeCustomize.headerSticky')}
          />
          <FlagSegment
            customize={customize}
            flag="motionHeader"
            label={t('admin.themeCustomize.motionHeader')}
          />
          <FlagSegment
            customize={customize}
            flag="motionReveal"
            label={t('admin.themeCustomize.motionReveal')}
          />
          <FlagSegment
            customize={customize}
            flag="headerSearch"
            label={t('admin.themeCustomize.headerSearch')}
          />
          <FlagSegment
            customize={customize}
            flag="headerTheme"
            label={t('admin.themeCustomize.headerTheme')}
          />
          <FlagSegment
            customize={customize}
            flag="headerTagline"
            label={t('admin.themeCustomize.headerTagline')}
          />
        </div>
      ) : null}
    </Card>
  )
}

export function AdvancedPanel({ customize }: { customize: ThemeCustomizePageState }) {
  const { t } = useTranslation()
  const [saveAsOpen, setSaveAsOpen] = useState(false)
  const [newName, setNewName] = useState('')
  const [newDescription, setNewDescription] = useState('')

  const closeSaveAs = () => {
    setSaveAsOpen(false)
    setNewName('')
    setNewDescription('')
  }

  return (
    <div>
      <PanelHead title={t('admin.themeWs.navAdvanced')} desc={t('admin.themeWs.descAdvanced')} />
      <ScopeNote
        kind="overrides"
        tagKey="admin.themeWs.tagOverridesInline"
        noteKey="admin.themeWs.scopeOverridesNote"
      />
      <WsCard title={t('admin.themeWs.cardTokens')} sub="#785 · authoring-guide">
        <TokenOverridesEditor
          tokens={customize.draft.tokens}
          disabled={customize.isLoading || customize.isSaving}
          onChange={(next) => {
            customize.setKnob('tokens', next)
          }}
        />
      </WsCard>
      <WsCard title={t('admin.themeCustomize.saveAsTheme')}>
        <Text muted variant="caption" className="mb-2 block">
          {t('admin.themeWs.saveAsHint')}
        </Text>
        <Button
          variant="secondary"
          disabled={!customize.canSaveAsTheme || customize.isCreating}
          onClick={() => {
            setSaveAsOpen(true)
          }}
        >
          {t('admin.themeCustomize.saveAsTheme')}
        </Button>
      </WsCard>

      <ConfirmDialog
        open={saveAsOpen}
        title={t('admin.themeCustomize.saveAsTitle')}
        description={t('admin.themeCustomize.saveAsDescription')}
        confirmLabel={t('admin.themeCustomize.saveAsConfirm')}
        cancelLabel={t('admin.themeCustomize.saveAsCancel')}
        isPending={customize.isCreating}
        confirmDisabled={newName.trim() === ''}
        onConfirm={() => {
          customize.saveAsNewTheme(newName, newDescription, {
            onSuccess: closeSaveAs,
            onError: () => {},
          })
        }}
        onCancel={closeSaveAs}
      >
        <Stack gap="sm">
          <Input
            id="save-as-theme-name"
            label={t('admin.themeCustomize.saveAsName')}
            value={newName}
            onChange={(event) => {
              setNewName(event.target.value)
            }}
          />
          <Textarea
            id="save-as-theme-description"
            label={t('admin.themeCustomize.saveAsDescriptionLabel')}
            value={newDescription}
            rows={2}
            onChange={(event) => {
              setNewDescription(event.target.value)
            }}
          />
        </Stack>
      </ConfirmDialog>
    </div>
  )
}

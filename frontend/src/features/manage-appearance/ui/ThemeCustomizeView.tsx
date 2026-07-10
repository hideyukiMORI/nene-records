import { type ReactNode, useMemo, useState } from 'react'
import { useMediaList } from '@/entities/media'
import { useThemeAuthoringGuide } from '@/entities/theme'
import { useTranslation } from '@/shared/i18n'
import { isSafeTokenValue, TOKEN_KEY } from '@/shared/lib/runtime-themes'
import {
  BRAND_FONT_OPTIONS,
  BRAND_SIZE_OPTIONS,
  DENSITY_OPTIONS,
  MENU_SIZE_OPTIONS,
  DISPLAY_FONT_OPTIONS,
  FLAG_DEFS,
  FONT_OPTIONS,
  FONT_SIZE_OPTIONS,
  GUTTER_OPTIONS,
  MONO_FONT_OPTIONS,
  RADIUS_OPTIONS,
  resolveDraftImageUrls,
  TYPE_SCALE_OPTIONS,
  WIDTH_OPTIONS,
  type KnobOption,
  type ThemeImages,
} from '@/shared/lib/theme-customization'
import { Button, Card, ConfirmDialog, Input, Stack, Text, Textarea } from '@/shared/ui'
import { THEME_PREVIEW_PARAM } from '@/shared/lib/theme-preview-protocol'
import { useThemePreviewSender } from '../hooks/useThemePreviewSender'
import type { ThemeCustomizePageState } from '../hooks/useThemeCustomizePage'
import { ThemeImageField } from './ThemeImageField'

const IMAGE_SLOTS: ReadonlyArray<keyof ThemeImages> = ['logo', 'hero', 'background']

function Field({ label, children }: { label: string; children: ReactNode }) {
  // Fixed-width label column + left-grouped control: keeps each control right
  // next to its label (no wide spread on large screens) while controls still
  // line up in a tidy column.
  return (
    <label className="flex items-center gap-inline-md">
      <span className="w-44 shrink-0 font-chrome text-caption font-semibold text-text-primary">
        {label}
      </span>
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

/** One documented optional engine token from the authoring guide. */
interface TokenCatalogEntry {
  token: string
  group: string
  drives: string
}

/**
 * Advanced token overrides (#785): the escape hatch that makes the documented
 * optional engine tokens reachable from the UI (no MCP/API). The catalog is the
 * authoring guide's `optionalTokens` — adding an engine token there makes it
 * appear here with no UI work. Values are re-validated at emission; an unsafe
 * value is highlighted and simply never emitted.
 */
function TokenOverridesEditor({
  tokens,
  disabled,
  onChange,
}: {
  tokens: Record<string, string> | undefined
  disabled: boolean
  onChange: (next: Record<string, string> | undefined) => void
}) {
  const { t } = useTranslation()
  const guideQuery = useThemeAuthoringGuide()
  const [pendingToken, setPendingToken] = useState('')

  const catalog = useMemo((): TokenCatalogEntry[] => {
    const raw = guideQuery.data?.renderModel['optionalTokens']
    if (typeof raw !== 'object' || raw === null) {
      return []
    }
    return Object.entries(raw as Record<string, unknown>).flatMap(
      ([token, doc]): TokenCatalogEntry[] => {
        if (!TOKEN_KEY.test(token)) return []
        const detail =
          typeof doc === 'object' && doc !== null ? (doc as Record<string, unknown>) : {}
        return [
          {
            token,
            group: typeof detail['group'] === 'string' ? detail['group'] : '',
            drives: typeof detail['drives'] === 'string' ? detail['drives'] : '',
          },
        ]
      },
    )
  }, [guideQuery.data])

  const current = tokens ?? {}
  const entries = Object.entries(current)
  const availableByGroup = useMemo(() => {
    const used = tokens ?? {}
    const groups = new Map<string, TokenCatalogEntry[]>()
    for (const entry of catalog) {
      if (entry.token in used) continue
      const list = groups.get(entry.group) ?? []
      list.push(entry)
      groups.set(entry.group, list)
    }
    return groups
  }, [catalog, tokens])

  const emit = (next: Record<string, string>): void => {
    onChange(Object.keys(next).length > 0 ? next : undefined)
  }

  return (
    <Stack gap="xs" className="border-t border-border pt-stack-sm">
      <Text muted variant="caption">
        {t('admin.themeCustomize.tokens.help')}
      </Text>
      {entries.map(([token, value]) => {
        const safe = isSafeTokenValue(value)
        return (
          <div key={token} className="flex items-center gap-inline-sm">
            <span className="w-44 shrink-0 truncate font-mono text-caption text-text-primary">
              --{token}
            </span>
            <input
              type="text"
              className={`min-w-0 flex-1 rounded-sm border bg-surface px-inline-sm py-stack-xs font-mono text-body-sm text-text-primary ${safe ? 'border-border' : 'border-danger'}`}
              value={value}
              disabled={disabled}
              aria-label={`--${token}`}
              aria-invalid={!safe}
              title={safe ? undefined : t('admin.themeCustomize.tokens.invalid')}
              onChange={(event) => {
                emit({ ...current, [token]: event.target.value })
              }}
            />
            <Button
              variant="ghost"
              size="sm"
              disabled={disabled}
              onClick={() => {
                emit(Object.fromEntries(entries.filter(([key]) => key !== token)))
              }}
            >
              {t('admin.themeCustomize.tokens.remove')}
            </Button>
          </div>
        )
      })}
      <div className="flex items-center gap-inline-sm">
        <select
          className="rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
          value={pendingToken}
          disabled={disabled}
          aria-label={t('admin.themeCustomize.tokens.placeholder')}
          onChange={(event) => {
            setPendingToken(event.target.value)
          }}
        >
          <option value="">{t('admin.themeCustomize.tokens.placeholder')}</option>
          {[...availableByGroup.entries()].map(([group, list]) => (
            <optgroup key={group} label={group}>
              {list.map((entry) => (
                <option key={entry.token} value={entry.token} title={entry.drives}>
                  --{entry.token}
                </option>
              ))}
            </optgroup>
          ))}
        </select>
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled || pendingToken === ''}
          onClick={() => {
            emit({ ...current, [pendingToken]: '' })
            setPendingToken('')
          }}
        >
          {t('admin.themeCustomize.tokens.add')}
        </Button>
      </div>
    </Stack>
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
  canSaveAsTheme,
  saveAsNewTheme,
  isCreating,
  isLoading,
  isSaving,
  isDirty,
}: ThemeCustomizePageState) {
  const { t } = useTranslation()
  const disabled = isLoading || isSaving
  const themePlaceholder = t('admin.themeCustomize.themeDefault')

  const [saveAsOpen, setSaveAsOpen] = useState(false)
  const [newName, setNewName] = useState('')
  const [newDescription, setNewDescription] = useState('')

  // Media library: the draft stores image ids, so we need id→url for thumbnails
  // and to resolve the preview (which reuses the public URL-based CSS builder).
  const mediaList = useMediaList()
  const idToUrl = useMemo(() => {
    const map = new Map<number, string>()
    for (const item of mediaList.data?.items ?? []) {
      map.set(item.id, item.url)
    }
    return map
  }, [mediaList.data?.items])

  const thumbUrl = (slot: keyof ThemeImages, mode: 'light' | 'dark'): string | undefined => {
    const value = draft.images?.[slot]?.[mode]
    if (typeof value === 'number') {
      return idToUrl.get(value)
    }
    return typeof value === 'string' ? value : undefined
  }

  const setImage = (
    slot: keyof ThemeImages,
    mode: 'light' | 'dark',
    id: number | undefined,
  ): void => {
    // Rebuild the slot map, pruning modes/slots that become empty (so storage and
    // the exactOptional types stay clean without dynamic `delete`).
    const nextImages: ThemeImages = {}
    for (const key of IMAGE_SLOTS) {
      const base = draft.images?.[key]
      const light = key === slot && mode === 'light' ? id : base?.light
      const dark = key === slot && mode === 'dark' ? id : base?.dark
      if (light !== undefined || dark !== undefined) {
        nextImages[key] = { light, dark }
      }
    }
    setKnob('images', Object.keys(nextImages).length > 0 ? nextImages : undefined)
  }

  const imagePickerLabels = {
    select: t('admin.blocks.media.select'),
    change: t('admin.blocks.media.change'),
    remove: t('admin.blocks.media.remove'),
  }

  // Live preview (#538 ②): an embedded public page the draft is pushed to. Image
  // ids are resolved to URLs first so hero/background render like the public site.
  const [showPreview, setShowPreview] = useState(false)
  const previewDraft = useMemo(
    () => resolveDraftImageUrls(draft, (id) => idToUrl.get(id)),
    [draft, idToUrl],
  )
  const { iframeRef } = useThemePreviewSender(themeId, previewDraft)

  const closeSaveAs = () => {
    setSaveAsOpen(false)
    setNewName('')
    setNewDescription('')
  }

  return (
    <Card padding="none" className="p-stack-md">
      <Stack gap="md">
        <div className="flex items-center justify-between gap-inline-md">
          <Text muted variant="caption">
            {t('admin.themeCustomize.intro', { theme: themeId })}
          </Text>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              setShowPreview((shown) => !shown)
            }}
          >
            {showPreview
              ? t('admin.themeCustomize.preview.hide')
              : t('admin.themeCustomize.preview.show')}
          </Button>
        </div>

        {/* Live preview: the draft is pushed into this public page via postMessage
            (see useThemePreviewSender / useThemePreviewBridge). */}
        {showPreview ? (
          <iframe
            ref={iframeRef}
            src={`/search?${THEME_PREVIEW_PARAM}=1`}
            title={t('admin.themeCustomize.preview.title')}
            className="w-full rounded-md border border-border"
            style={{ height: 480 }}
          />
        ) : null}

        {/* Basics — the essentials a non-engineer reaches for first. */}
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
          <Field label={t('admin.themeCustomize.fontDisplay')}>
            <Select
              value={draft.fontDisplay}
              options={DISPLAY_FONT_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('fontDisplay', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.fontMono')}>
            <Select
              value={draft.fontMono}
              options={MONO_FONT_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('fontMono', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.fontBrand')}>
            <Select
              value={draft.fontBrand}
              options={BRAND_FONT_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('fontBrand', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.brandSize')}>
            <Select
              value={draft.brandSize}
              options={BRAND_SIZE_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('brandSize', v)
              }}
            />
          </Field>
          <Field label={t('admin.themeCustomize.menuSize')}>
            <Select
              value={draft.menuSize}
              options={MENU_SIZE_OPTIONS}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('menuSize', v)
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
          <Field label={t('admin.themeCustomize.feedColumns')}>
            <Select
              value={draft.flags?.feedColumns}
              options={FLAG_DEFS.feedColumns.options}
              placeholder={themePlaceholder}
              onChange={(v) => {
                setKnob('flags', { ...draft.flags, feedColumns: v })
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
        </Stack>

        {/* Images — logo / hero / background, per light/dark, from the media library (#372). */}
        <Stack gap="sm" className="border-t border-border pt-stack-sm">
          <Text muted variant="caption">
            {t('admin.themeCustomize.imagesGroup')}
          </Text>
          <ThemeImageField
            label={t('admin.themeCustomize.logo')}
            lightLabel="light"
            darkLabel="dark"
            pickerLabels={imagePickerLabels}
            value={draft.images?.logo}
            urlFor={(mode) => thumbUrl('logo', mode)}
            disabled={disabled}
            onChange={(mode, id) => {
              setImage('logo', mode, id)
            }}
          />
          <ThemeImageField
            label={t('admin.themeCustomize.heroImage')}
            lightLabel="light"
            darkLabel="dark"
            pickerLabels={imagePickerLabels}
            value={draft.images?.hero}
            urlFor={(mode) => thumbUrl('hero', mode)}
            disabled={disabled}
            onChange={(mode, id) => {
              setImage('hero', mode, id)
            }}
          />
          <ThemeImageField
            label={t('admin.themeCustomize.background')}
            lightLabel="light"
            darkLabel="dark"
            pickerLabels={imagePickerLabels}
            value={draft.images?.background}
            urlFor={(mode) => thumbUrl('background', mode)}
            disabled={disabled}
            onChange={(mode, id) => {
              setImage('background', mode, id)
            }}
          />
        </Stack>

        {/* Advanced — finer tuning + structural flags, folded away by default. */}
        <details className="border-t border-border pt-stack-sm">
          <summary className="cursor-pointer font-chrome text-caption font-semibold text-text-primary">
            {t('admin.themeCustomize.advanced')}
          </summary>
          <Stack gap="sm" className="mt-stack-sm">
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
            <Field label={t('admin.themeCustomize.headerSearch')}>
              <Select
                value={draft.flags?.headerSearch}
                options={FLAG_DEFS.headerSearch.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerSearch: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.headerTheme')}>
              <Select
                value={draft.flags?.headerTheme}
                options={FLAG_DEFS.headerTheme.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerTheme: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.headerTagline')}>
              <Select
                value={draft.flags?.headerTagline}
                options={FLAG_DEFS.headerTagline.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerTagline: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.headerLayout')}>
              <Select
                value={draft.flags?.headerLayout}
                options={FLAG_DEFS.headerLayout.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerLayout: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.headerNavAlign')}>
              <Select
                value={draft.flags?.headerNavAlign}
                options={FLAG_DEFS.headerNavAlign.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerNavAlign: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.headerDensity')}>
              <Select
                value={draft.flags?.headerDensity}
                options={FLAG_DEFS.headerDensity.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerDensity: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.headerWidth')}>
              <Select
                value={draft.flags?.headerWidth}
                options={FLAG_DEFS.headerWidth.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerWidth: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.headerSticky')}>
              <Select
                value={draft.flags?.headerSticky}
                options={FLAG_DEFS.headerSticky.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, headerSticky: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.motionReveal')}>
              <Select
                value={draft.flags?.motionReveal}
                options={FLAG_DEFS.motionReveal.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, motionReveal: v })
                }}
              />
            </Field>
            <Field label={t('admin.themeCustomize.motionHeader')}>
              <Select
                value={draft.flags?.motionHeader}
                options={FLAG_DEFS.motionHeader.options}
                placeholder={themePlaceholder}
                onChange={(v) => {
                  setKnob('flags', { ...draft.flags, motionHeader: v })
                }}
              />
            </Field>
            {/* Documented engine tokens, editable without MCP/API (#785). */}
            <TokenOverridesEditor
              tokens={draft.tokens}
              disabled={disabled}
              onChange={(next) => {
                setKnob('tokens', next)
              }}
            />
          </Stack>
        </details>

        <div className="flex items-center gap-inline-sm">
          <Button onClick={save} disabled={disabled || !isDirty}>
            {isSaving ? t('admin.themeCustomize.saving') : t('admin.themeCustomize.save')}
          </Button>
          <Button variant="ghost" onClick={reset} disabled={disabled}>
            {t('admin.themeCustomize.reset')}
          </Button>
          <Button
            variant="ghost"
            className="ml-auto"
            onClick={() => {
              setSaveAsOpen(true)
            }}
            disabled={disabled || !canSaveAsTheme}
          >
            {t('admin.themeCustomize.saveAsTheme')}
          </Button>
        </div>
      </Stack>

      <ConfirmDialog
        open={saveAsOpen}
        title={t('admin.themeCustomize.saveAsTitle')}
        description={t('admin.themeCustomize.saveAsDescription')}
        confirmLabel={t('admin.themeCustomize.saveAsConfirm')}
        cancelLabel={t('admin.themeCustomize.saveAsCancel')}
        isPending={isCreating}
        confirmDisabled={newName.trim() === ''}
        onConfirm={() => {
          saveAsNewTheme(newName, newDescription, { onSuccess: closeSaveAs, onError: () => {} })
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
    </Card>
  )
}

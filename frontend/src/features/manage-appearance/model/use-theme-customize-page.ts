import { useState } from 'react'
import { useUpdateSetting, useSettingList } from '@/entities/setting'
import { type ThemeManifestDto, useCreateTheme, useThemes } from '@/entities/theme'
import { useTranslation } from '@/shared/i18n'
import { resolvePublicThemeId } from '@/shared/lib/public-themes'
import { parseThemeOverrides, type ThemeOverrides } from '@/shared/lib/theme-customization'
import {
  type BaseThemeTokens,
  baseThemeTokens,
  buildThemeManifestFromCustomization,
  builtInThemeIds,
  slugifyThemeId,
  uniqueThemeId,
} from '@/shared/lib/theme-from-customization'
import { useToast } from '@/shared/ui'

const ACTIVE_THEME_KEY = 'active_theme'
const OVERRIDES_KEY = 'theme_overrides'

export interface ThemeCustomizePageState {
  /** The theme being customized (the active public theme). */
  themeId: string
  /** Current draft overrides (local edits, not yet saved). */
  draft: ThemeOverrides
  /** Update one knob in the draft (undefined / '' clears it). */
  setKnob: <K extends keyof ThemeOverrides>(knob: K, value: ThemeOverrides[K] | undefined) => void
  /** Persist the draft for the active theme. */
  save: () => void
  /** Clear this theme's overrides. */
  reset: () => void
  /** Whether the current customization can be captured as a new runtime theme. */
  canSaveAsTheme: boolean
  /** Save the current base + draft as a brand-new runtime theme (createTheme). */
  saveAsNewTheme: (
    name: string,
    description: string,
    opts: { onSuccess: () => void; onError: (message?: string) => void },
  ) => void
  isCreating: boolean
  isLoading: boolean
  isSaving: boolean
  isDirty: boolean
}

/** Drop undefined/empty values (and round-trip) so storage stays clean. */
function clean(overrides: ThemeOverrides): ThemeOverrides {
  return JSON.parse(JSON.stringify(overrides)) as ThemeOverrides
}

/**
 * Admin controller for the theme customizer. Reads/writes the per-theme knob
 * overrides in the `theme_overrides` setting (JSON), scoped to the active theme.
 */
export function useThemeCustomizePage(): ThemeCustomizePageState {
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()
  const themesQuery = useThemes()
  const createTheme = useCreateTheme()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const items = settingsQuery.data?.items
  const themeId = resolvePublicThemeId(
    items?.find((item) => item.settingKey === ACTIVE_THEME_KEY)?.value,
  )
  const storedRaw = items?.find((item) => item.settingKey === OVERRIDES_KEY)?.value
  const stored = parseThemeOverrides(storedRaw)[themeId] ?? {}
  const storedKey = JSON.stringify({ themeId, stored })

  // Re-sync the draft when the loaded value / active theme changes. Render-time
  // state adjustment (React's sanctioned pattern) — no effect, no flash.
  const [draft, setDraft] = useState<ThemeOverrides>(stored)
  const [syncedKey, setSyncedKey] = useState(storedKey)
  if (storedKey !== syncedKey) {
    setSyncedKey(storedKey)
    setDraft(stored)
  }

  const setKnob: ThemeCustomizePageState['setKnob'] = (knob, value) => {
    setDraft((prev) => ({
      ...prev,
      [knob]: value === undefined || value === '' ? undefined : value,
    }))
  }

  const persist = (nextForTheme: ThemeOverrides): void => {
    const cleaned = clean(nextForTheme)
    const all = parseThemeOverrides(storedRaw)
    const next: Record<string, ThemeOverrides> = {}
    for (const [id, value] of Object.entries(all)) {
      if (id !== themeId) {
        next[id] = value
      }
    }
    if (Object.keys(cleaned).length > 0) {
      next[themeId] = cleaned
    }
    updateSetting.mutate(
      { settingKey: OVERRIDES_KEY, input: { value: JSON.stringify(next) } },
      {
        onSuccess: () => {
          showToast(t('admin.themeCustomize.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.themeCustomize.saveError'), 'error')
        },
      },
    )
  }

  // Full token base for the current theme: built-in (from extracted data) or,
  // when customizing a runtime theme, that theme's own manifest tokens.
  const runtimeThemes = themesQuery.data?.items ?? []
  const base: BaseThemeTokens | undefined =
    baseThemeTokens(themeId) ??
    (() => {
      const tokens = runtimeThemes.find((theme) => theme.theme_key === themeId)?.manifest.tokens
      const light = tokens?.light
      const dark = tokens?.dark
      return light !== undefined && dark !== undefined ? { light, dark } : undefined
    })()

  const takenIds = new Set<string>([
    ...builtInThemeIds(),
    ...runtimeThemes.map((theme) => theme.theme_key),
  ])

  return {
    themeId,
    draft,
    setKnob,
    save: () => {
      persist(draft)
    },
    reset: () => {
      setDraft({})
      persist({})
    },
    canSaveAsTheme: base !== undefined,
    saveAsNewTheme: (name, description, opts) => {
      if (base === undefined) {
        opts.onError(t('admin.themeCustomize.saveAsUnavailable'))
        return
      }
      const id = uniqueThemeId(slugifyThemeId(name), takenIds)
      const manifest = buildThemeManifestFromCustomization({
        id,
        name: name.trim(),
        ...(description.trim() === '' ? {} : { description }),
        baseTokens: base,
        overrides: clean(draft),
      })
      createTheme.mutate(manifest as unknown as ThemeManifestDto, {
        onSuccess: () => {
          showToast(t('admin.themeCustomize.savedAsTheme'), 'success')
          opts.onSuccess()
        },
        onError: () => {
          showToast(t('admin.themeCustomize.saveAsError'), 'error')
          opts.onError()
        },
      })
    },
    isCreating: createTheme.isPending,
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty: JSON.stringify(clean(draft)) !== JSON.stringify(stored),
  }
}

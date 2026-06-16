import { useState } from 'react'
import { useUpdateSetting, useSettingList } from '@/entities/setting'
import { resolvePublicThemeId } from '@/shared/lib/public-themes'
import { parseThemeOverrides, type ThemeOverrides } from '@/shared/lib/theme-customization'

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
    updateSetting.mutate({ settingKey: OVERRIDES_KEY, input: { value: JSON.stringify(next) } })
  }

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
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty: JSON.stringify(clean(draft)) !== JSON.stringify(stored),
  }
}

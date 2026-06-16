import { useUpdateSetting, useSettingList } from '@/entities/setting'
import {
  DEFAULT_PUBLIC_THEME_ID,
  PUBLIC_THEMES,
  resolvePublicThemeId,
  type PublicThemeMeta,
} from '@/shared/lib/public-themes'

const ACTIVE_THEME_KEY = 'active_theme'

export interface PublicThemePageState {
  themes: readonly PublicThemeMeta[]
  activeThemeId: string
  selectTheme: (themeId: string) => void
  isLoading: boolean
  isSaving: boolean
  pendingThemeId: string | null
}

/**
 * Admin controller for the public-site theme picker. Reads the `active_theme`
 * setting and writes the chosen theme id back through the settings API.
 */
export function usePublicThemePage(): PublicThemePageState {
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()

  const stored = settingsQuery.data?.items.find(
    (item) => item.settingKey === ACTIVE_THEME_KEY,
  )?.value
  const activeThemeId = resolvePublicThemeId(stored ?? DEFAULT_PUBLIC_THEME_ID)

  const selectTheme = (themeId: string): void => {
    if (themeId === activeThemeId) {
      return
    }
    updateSetting.mutate({ settingKey: ACTIVE_THEME_KEY, input: { value: themeId } })
  }

  return {
    themes: PUBLIC_THEMES,
    activeThemeId,
    selectTheme,
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    pendingThemeId: updateSetting.isPending ? updateSetting.variables.input.value : null,
  }
}

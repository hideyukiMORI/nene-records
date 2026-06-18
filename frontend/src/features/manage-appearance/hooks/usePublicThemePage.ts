import { useMemo } from 'react'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import { type ThemeDto, useThemes } from '@/entities/theme'
import {
  DEFAULT_PUBLIC_THEME_ID,
  PUBLIC_THEMES,
  type PublicThemeMeta,
  resolvePublicThemeId,
} from '@/shared/lib/public-themes'
import { type RuntimeThemeManifest, swatchFromManifest } from '@/shared/lib/runtime-themes'

const ACTIVE_THEME_KEY = 'active_theme'

export interface PublicThemePageState {
  themes: readonly PublicThemeMeta[]
  activeThemeId: string
  selectTheme: (themeId: string) => void
  isLoading: boolean
  isSaving: boolean
  pendingThemeId: string | null
}

/** Adapt a stored runtime theme into a picker card (swatch from its tokens). */
function runtimeThemeMeta(theme: ThemeDto): PublicThemeMeta {
  const manifest = theme.manifest as RuntimeThemeManifest
  return {
    id: theme.theme_key,
    name: theme.name,
    description: typeof manifest.description === 'string' ? manifest.description : 'Runtime theme.',
    author: typeof manifest.author === 'string' ? manifest.author : 'Runtime',
    version: theme.version,
    createdAt: theme.created_at.slice(0, 10),
    preview: swatchFromManifest(manifest),
  }
}

/**
 * Admin controller for the public-site theme picker. Reads the `active_theme`
 * setting and writes the chosen theme id back through the settings API. The
 * card list composes built-in themes with runtime (data-driven) themes from the
 * API, so admins can pick a ClaudeDesign-registered theme too (#423 Phase E).
 */
export function usePublicThemePage(): PublicThemePageState {
  const settingsQuery = useSettingList()
  const themesQuery = useThemes()
  const updateSetting = useUpdateSetting()

  const runtimeThemes = useMemo(() => themesQuery.data?.items ?? [], [themesQuery.data?.items])
  const themes = useMemo<readonly PublicThemeMeta[]>(
    () => [...PUBLIC_THEMES, ...runtimeThemes.map(runtimeThemeMeta)],
    [runtimeThemes],
  )

  const stored = settingsQuery.data?.items.find(
    (item) => item.settingKey === ACTIVE_THEME_KEY,
  )?.value
  // A runtime active theme keeps its own key; otherwise coerce to a built-in.
  const runtimeKeys = new Set(runtimeThemes.map((theme) => theme.theme_key))
  const activeThemeId =
    stored !== undefined && runtimeKeys.has(stored)
      ? stored
      : resolvePublicThemeId(stored ?? DEFAULT_PUBLIC_THEME_ID)

  const selectTheme = (themeId: string): void => {
    if (themeId === activeThemeId) {
      return
    }
    updateSetting.mutate({ settingKey: ACTIVE_THEME_KEY, input: { value: themeId } })
  }

  return {
    themes,
    activeThemeId,
    selectTheme,
    isLoading: settingsQuery.isLoading || themesQuery.isLoading,
    isSaving: updateSetting.isPending,
    pendingThemeId: updateSetting.isPending ? updateSetting.variables.input.value : null,
  }
}

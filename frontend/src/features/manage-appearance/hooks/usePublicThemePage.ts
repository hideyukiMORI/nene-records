import { useMemo } from 'react'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import {
  type ThemeDto,
  type ThemeManifestDto,
  useDeleteTheme,
  useThemes,
  useUpdateTheme,
} from '@/entities/theme'
import { useTranslation } from '@/shared/i18n'
import {
  DEFAULT_PUBLIC_THEME_ID,
  PUBLIC_THEMES,
  type PublicThemeMeta,
  resolvePublicThemeId,
} from '@/shared/lib/public-themes'
import { type RuntimeThemeManifest, swatchFromManifest } from '@/shared/lib/runtime-themes'
import { useToast } from '@/shared/ui'

const ACTIVE_THEME_KEY = 'active_theme'

export interface PublicThemePageState {
  themes: readonly PublicThemeMeta[]
  activeThemeId: string
  selectTheme: (themeId: string) => void
  isLoading: boolean
  isSaving: boolean
  pendingThemeId: string | null
  /** Runtime themes (data-driven) — those the admin may edit/delete. */
  runtimeThemes: readonly ThemeDto[]
  /** Keys of runtime themes (built-in themes are not editable/deletable). */
  runtimeKeys: ReadonlySet<string>
  deleteTheme: (themeKey: string) => void
  updateTheme: (
    themeKey: string,
    manifest: ThemeManifestDto,
    opts: { onSuccess: () => void; onError: (message: string) => void },
  ) => void
  isMutating: boolean
}

/**
 * Adapt a stored runtime theme into a picker card. Uses the server-resolved
 * `thumbnail_url` (from `assets.preview` media id, #426 A) when present;
 * otherwise the card falls back to a swatch derived from the theme's tokens.
 */
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
    thumbnail: theme.thumbnail_url !== '' ? theme.thumbnail_url : undefined,
  }
}

/** Structural shape of a problem-details error (subset of AppError). */
interface ProblemErrorLike {
  detail?: string
  title?: string
  errors?: readonly { field: string; message: string }[]
}

/** A readable message from a problem-details error (joins field errors). */
function errorMessage(error: ProblemErrorLike, fallback: string): string {
  if (error.errors !== undefined && error.errors.length > 0) {
    return error.errors.map((e) => `${e.field}: ${e.message}`).join('; ')
  }
  return error.detail ?? error.title ?? fallback
}

/**
 * Admin controller for the public-site theme picker. Reads the `active_theme`
 * setting and writes the chosen theme id back through the settings API. The
 * card list composes built-in themes with runtime (data-driven) themes from the
 * API; runtime themes can also be edited (manifest) and deleted (#423 Phase E).
 */
export function usePublicThemePage(): PublicThemePageState {
  const settingsQuery = useSettingList()
  const themesQuery = useThemes()
  const updateSetting = useUpdateSetting()
  const deleteMutation = useDeleteTheme()
  const updateMutation = useUpdateTheme()
  const { showToast } = useToast()
  const { t } = useTranslation()

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

  const deleteTheme = (themeKey: string): void => {
    deleteMutation.mutate(themeKey, {
      onSuccess: () => {
        showToast(t('admin.publicTheme.deleted'), 'success')
      },
      onError: (error) => {
        showToast(errorMessage(error, t('admin.publicTheme.deleteError')), 'error')
      },
    })
  }

  const updateTheme: PublicThemePageState['updateTheme'] = (themeKey, manifest, opts) => {
    updateMutation.mutate(
      { key: themeKey, manifest },
      {
        onSuccess: () => {
          showToast(t('admin.publicTheme.updated'), 'success')
          opts.onSuccess()
        },
        onError: (error) => {
          opts.onError(errorMessage(error, t('admin.publicTheme.updateError')))
        },
      },
    )
  }

  return {
    themes,
    activeThemeId,
    selectTheme,
    isLoading: settingsQuery.isLoading || themesQuery.isLoading,
    isSaving: updateSetting.isPending,
    pendingThemeId: updateSetting.isPending ? updateSetting.variables.input.value : null,
    runtimeThemes,
    runtimeKeys,
    deleteTheme,
    updateTheme,
    isMutating: deleteMutation.isPending || updateMutation.isPending,
  }
}

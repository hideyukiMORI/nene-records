import { useCallback, useMemo, useState } from 'react'
import type { SettingItem, SettingRevision } from '@/entities/setting'
import { useSettingList, useSettingRevisions, useUpdateSetting } from '@/entities/setting'

/**
 * Settings with a dedicated editor elsewhere are hidden from this generic
 * key-value form so they aren't edited twice — and, crucially, so non-technical
 * admins never face a raw JSON string here (#541). Each key below has a
 * purpose-built, structured editor:
 *
 * - `front_page`     → the "Home page display" section on this page (#701)
 * - `theme_overrides`→ Appearance → Theme (ThemeCustomizeView: colors/fonts/knobs)
 * - `header_config`  → Appearance → Theme (HeaderContentView: topbar/CTA toggles)
 * - `home_hero`      → Appearance → Theme (HomeHeroView: typed block editor, #486)
 * - `layout_config`  → Appearance → Layout (LayoutConfigBar: columns/position)
 * - `active_theme`   → Appearance → Theme (PublicThemeView: theme picker)
 */
const DEDICATED_UI_KEYS = new Set([
  'front_page',
  'theme_overrides',
  'header_config',
  'home_hero',
  'layout_config',
  'active_theme',
])

export interface ManageSiteSettingsPageState {
  items: SettingItem[]
  isLoading: boolean
  isError: boolean
  isSaving: boolean
  expandedKey: string | null
  revisions: SettingRevision[]
  revisionsLoading: boolean
  revisionsError: boolean
  onRetry: () => void
  onSave: (settingKey: string, value: string) => Promise<void>
  onToggleExpanded: (settingKey: string) => void
}

export function useManageSiteSettingsPage(): ManageSiteSettingsPageState {
  const listQuery = useSettingList()
  const updateMutation = useUpdateSetting()
  const [expandedKey, setExpandedKey] = useState<string | null>(null)

  // Fetch revisions only for the currently expanded setting
  const revisionsQuery = useSettingRevisions(expandedKey ?? '')

  const onSave = useCallback(
    async (settingKey: string, value: string) => {
      await updateMutation.mutateAsync({ settingKey, input: { value } })
    },
    [updateMutation],
  )

  const items = useMemo(
    () => (listQuery.data?.items ?? []).filter((item) => !DEDICATED_UI_KEYS.has(item.settingKey)),
    [listQuery.data?.items],
  )

  return {
    items,
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    isSaving: updateMutation.isPending,
    expandedKey,
    revisions: revisionsQuery.data?.items ?? [],
    revisionsLoading: revisionsQuery.isLoading,
    revisionsError: revisionsQuery.isError,
    onRetry: () => {
      void listQuery.refetch()
    },
    onSave,
    onToggleExpanded: (settingKey: string) => {
      setExpandedKey((cur) => (cur === settingKey ? null : settingKey))
    },
  }
}

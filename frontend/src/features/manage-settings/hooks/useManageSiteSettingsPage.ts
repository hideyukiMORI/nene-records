import { useCallback, useMemo, useState } from 'react'
import type { SettingItem, SettingRevision } from '@/entities/setting'
import { useSettingList, useSettingRevisions, useUpdateSetting } from '@/entities/setting'

/**
 * Settings with a dedicated editor elsewhere are hidden from this generic
 * key-value form so they aren't edited twice (once here as a raw field, once in
 * their purpose-built UI). `front_page` has the "Home page display" section (#701).
 */
const DEDICATED_UI_KEYS = new Set(['front_page'])

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

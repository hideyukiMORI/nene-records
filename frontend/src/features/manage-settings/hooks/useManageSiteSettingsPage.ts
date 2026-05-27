import { useCallback, useMemo, useState } from 'react'
import type { SettingItem, SettingRevision } from '@/entities/setting'
import { useSettingList, useSettingRevisions, useUpdateSetting } from '@/entities/setting'

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

  const items = useMemo(() => listQuery.data?.items ?? [], [listQuery.data?.items])

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

import { useState } from 'react'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import {
  parseRecordPageConfig,
  serializeRecordPageConfig,
  type RecordPageConfig,
} from '@/shared/lib/record-page-config'
import { useToast } from '@/shared/ui'

const RECORD_PAGE_CONFIG_KEY = 'record_page_config'

export interface RecordPageDisplayState {
  config: RecordPageConfig
  setComments: (value: boolean) => void
  setRelated: (value: boolean) => void
  isLoading: boolean
  isSaving: boolean
  isDirty: boolean
  save: () => void
}

/**
 * Admin controller for the "record page display" settings section (#775):
 * the site-wide defaults for the public record page's comments section and
 * related-records block, persisted as JSON in `record_page_config`.
 * Per-record `show_comments` / `show_related` (null = inherit) override these.
 */
export function useRecordPageDisplay(): RecordPageDisplayState {
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const stored =
    settingsQuery.data?.items.find((item) => item.settingKey === RECORD_PAGE_CONFIG_KEY)?.value ??
    ''
  const storedConfig = parseRecordPageConfig(stored)

  const [config, setConfig] = useState<RecordPageConfig>(storedConfig)

  // Re-sync when the persisted value changes (e.g. after a save elsewhere).
  const [syncedStored, setSyncedStored] = useState(stored)
  if (stored !== syncedStored) {
    setSyncedStored(stored)
    setConfig(storedConfig)
  }

  const isDirty =
    config.comments !== storedConfig.comments || config.related !== storedConfig.related

  const save = (): void => {
    updateSetting.mutate(
      { settingKey: RECORD_PAGE_CONFIG_KEY, input: { value: serializeRecordPageConfig(config) } },
      {
        onSuccess: () => {
          showToast(t('admin.recordPage.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.recordPage.saveError'), 'error')
        },
      },
    )
  }

  return {
    config,
    setComments: (value) => {
      setConfig((cur) => ({ ...cur, comments: value }))
    },
    setRelated: (value) => {
      setConfig((cur) => ({ ...cur, related: value }))
    },
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty,
    save,
  }
}

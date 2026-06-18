import { useState } from 'react'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import {
  type HeaderConfig,
  type HeaderCta,
  type HeaderTopbar,
  parseHeaderConfig,
  serializeHeaderConfig,
} from '@/shared/lib/header-config'
import { useToast } from '@/shared/ui'

const HEADER_CONFIG_KEY = 'header_config'

export interface HeaderConfigPageState {
  /** Current draft (local edits, not yet saved). */
  draft: HeaderConfig
  /** Patch the Top bar fields. */
  setTopbar: (patch: Partial<HeaderTopbar>) => void
  /** Patch the CTA fields. */
  setCta: (patch: Partial<HeaderCta>) => void
  /** Persist the draft to the `header_config` setting. */
  save: () => void
  isLoading: boolean
  isSaving: boolean
  isDirty: boolean
}

/**
 * Admin controller for public-site header content — reads/writes the Top bar
 * (phone / email / free text) and CTA (label + URL) in the `header_config`
 * setting (JSON). Skeleton/visibility live in the theme customizer's flags.
 */
export function useHeaderConfigPage(): HeaderConfigPageState {
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const storedRaw = settingsQuery.data?.items.find(
    (item) => item.settingKey === HEADER_CONFIG_KEY,
  )?.value
  const stored = parseHeaderConfig(storedRaw)
  const storedKey = JSON.stringify(stored)

  // Re-sync the draft when the loaded value changes (render-time adjustment).
  const [draft, setDraft] = useState<HeaderConfig>(stored)
  const [syncedKey, setSyncedKey] = useState(storedKey)
  if (storedKey !== syncedKey) {
    setSyncedKey(storedKey)
    setDraft(stored)
  }

  const setTopbar: HeaderConfigPageState['setTopbar'] = (patch) => {
    setDraft((prev) => ({ ...prev, topbar: { ...prev.topbar, ...patch } }))
  }
  const setCta: HeaderConfigPageState['setCta'] = (patch) => {
    setDraft((prev) => ({ ...prev, cta: { ...prev.cta, ...patch } }))
  }

  const save = (): void => {
    updateSetting.mutate(
      { settingKey: HEADER_CONFIG_KEY, input: { value: serializeHeaderConfig(draft) } },
      {
        onSuccess: () => {
          showToast(t('admin.headerContent.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.headerContent.saveError'), 'error')
        },
      },
    )
  }

  return {
    draft,
    setTopbar,
    setCta,
    save,
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty: JSON.stringify(draft) !== storedKey,
  }
}

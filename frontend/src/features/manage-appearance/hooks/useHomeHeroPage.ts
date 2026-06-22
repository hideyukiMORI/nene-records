import { useState } from 'react'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

const HOME_HERO_KEY = 'home_hero'
const EMPTY_DOCUMENT = '[]'

export interface HomeHeroPageState {
  /** Current draft — a JSON-string blocks document (one hero block, or empty). */
  draft: string
  setDraft: (value: string) => void
  /** Persist the draft to the `home_hero` setting. */
  save: () => void
  isLoading: boolean
  isSaving: boolean
  isDirty: boolean
}

/**
 * Admin controller for the public home masthead. Stores a one-block hero document
 * in the `home_hero` setting; the consumer renders it (with fallback to the
 * auto stats-hero when empty). Reuses the typed-block editor (#486) for editing.
 */
export function useHomeHeroPage(): HomeHeroPageState {
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const stored =
    settingsQuery.data?.items.find((item) => item.settingKey === HOME_HERO_KEY)?.value ??
    EMPTY_DOCUMENT

  // Re-sync the draft when the loaded value changes (render-time adjustment).
  const [draft, setDraft] = useState(stored)
  const [syncedKey, setSyncedKey] = useState(stored)
  if (stored !== syncedKey) {
    setSyncedKey(stored)
    setDraft(stored)
  }

  const save = (): void => {
    updateSetting.mutate(
      { settingKey: HOME_HERO_KEY, input: { value: draft } },
      {
        onSuccess: () => {
          showToast(t('admin.homeHero.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.homeHero.saveError'), 'error')
        },
      },
    )
  }

  return {
    draft,
    setDraft,
    save,
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty: draft !== stored,
  }
}

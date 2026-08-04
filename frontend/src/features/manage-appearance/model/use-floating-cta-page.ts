import { useState } from 'react'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import {
  type FloatingCtaConditions,
  type FloatingCtaConfig,
  parseFloatingCta,
  serializeFloatingCta,
} from '@/shared/lib/floating-cta'
import { useToast } from '@/shared/ui'

const FLOATING_CTA_KEY = 'floating_cta'

export interface FloatingCtaPageState {
  /** Current draft (local edits, not yet saved). */
  draft: FloatingCtaConfig
  /** Patch top-level fields (enabled / position / accent). */
  setConfig: (
    patch: Partial<
      Pick<
        FloatingCtaConfig,
        | 'enabled'
        | 'position'
        | 'accent'
        | 'bottomOffset'
        | 'dismissible'
        | 'trigger'
        | 'triggerValue'
      >
    >,
  ) => void
  /** Patch the content fields (icon / label / sub). */
  setContent: (patch: Partial<FloatingCtaConfig['content']>) => void
  /** Patch the link fields (url / newTab). */
  setLink: (patch: Partial<FloatingCtaConfig['link']>) => void
  /** Patch the display conditions. */
  setConditions: (patch: Partial<FloatingCtaConditions>) => void
  /** Persist the draft to the `floating_cta` setting. */
  save: () => void
  isLoading: boolean
  isSaving: boolean
  isDirty: boolean
}

/**
 * Admin controller for the public-site floating CTA (#982 P1) — reads/writes the
 * `floating_cta` setting (JSON). The server (`FloatingCtaValidator`) is the trust
 * boundary; this drives the editor draft and delegates persistence to the settings
 * mutation.
 */
export function useFloatingCtaPage(): FloatingCtaPageState {
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const storedRaw = settingsQuery.data?.items.find(
    (item) => item.settingKey === FLOATING_CTA_KEY,
  )?.value
  const stored = parseFloatingCta(storedRaw)
  const storedKey = JSON.stringify(stored)

  // Re-sync the draft when the loaded value changes (render-time adjustment).
  const [draft, setDraft] = useState<FloatingCtaConfig>(stored)
  const [syncedKey, setSyncedKey] = useState(storedKey)
  if (storedKey !== syncedKey) {
    setSyncedKey(storedKey)
    setDraft(stored)
  }

  const setConfig: FloatingCtaPageState['setConfig'] = (patch) => {
    setDraft((prev) => ({ ...prev, ...patch }))
  }
  const setContent: FloatingCtaPageState['setContent'] = (patch) => {
    setDraft((prev) => ({ ...prev, content: { ...prev.content, ...patch } }))
  }
  const setLink: FloatingCtaPageState['setLink'] = (patch) => {
    setDraft((prev) => ({ ...prev, link: { ...prev.link, ...patch } }))
  }
  const setConditions: FloatingCtaPageState['setConditions'] = (patch) => {
    setDraft((prev) => ({ ...prev, conditions: { ...prev.conditions, ...patch } }))
  }

  const save = (): void => {
    updateSetting.mutate(
      { settingKey: FLOATING_CTA_KEY, input: { value: serializeFloatingCta(draft) } },
      {
        onSuccess: () => {
          showToast(t('admin.floatingCta.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.floatingCta.saveError'), 'error')
        },
      },
    )
  }

  return {
    draft,
    setConfig,
    setContent,
    setLink,
    setConditions,
    save,
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty: JSON.stringify(draft) !== storedKey,
  }
}

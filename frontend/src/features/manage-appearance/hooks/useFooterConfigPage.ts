import { useState } from 'react'
import { useSettingList, useUpdateSetting } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import {
  type FooterBanner,
  type FooterConfig,
  type FooterCta,
  type FooterLegalLink,
  type FooterSocialLink,
  parseFooterConfig,
  serializeFooterConfig,
} from '@/shared/lib/footer-config'
import { useToast } from '@/shared/ui'

const FOOTER_CONFIG_KEY = 'footer_config'

export interface FooterConfigPageState {
  /** Current draft (local edits, not yet saved). */
  draft: FooterConfig
  /** Replace the social link list. */
  setSocial: (social: FooterSocialLink[]) => void
  /** Replace the legal link list. */
  setLegalLinks: (legalLinks: FooterLegalLink[]) => void
  /** Toggle the Powered-by note. */
  setShowPoweredBy: (show: boolean) => void
  /** Patch the Above-footer CTA row. */
  setCta: (patch: Partial<FooterCta>) => void
  /** Replace the banner list. */
  setBanners: (banners: FooterBanner[]) => void
  /** Persist the draft to the `footer_config` setting. */
  save: () => void
  isLoading: boolean
  isSaving: boolean
  isDirty: boolean
}

/**
 * Admin controller for public-site footer content (#766) — social icon links,
 * the legal link bar, and the Powered-by visibility, stored in the
 * `footer_config` setting (JSON). The typed sibling of useHeaderConfigPage.
 */
export function useFooterConfigPage(): FooterConfigPageState {
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()
  const { showToast } = useToast()
  const { t } = useTranslation()

  const storedRaw = settingsQuery.data?.items.find(
    (item) => item.settingKey === FOOTER_CONFIG_KEY,
  )?.value
  const stored = parseFooterConfig(storedRaw)
  const storedKey = JSON.stringify(stored)

  // Re-sync the draft when the loaded value changes (render-time adjustment).
  const [draft, setDraft] = useState<FooterConfig>(stored)
  const [syncedKey, setSyncedKey] = useState(storedKey)
  if (storedKey !== syncedKey) {
    setSyncedKey(storedKey)
    setDraft(stored)
  }

  const save = (): void => {
    updateSetting.mutate(
      { settingKey: FOOTER_CONFIG_KEY, input: { value: serializeFooterConfig(draft) } },
      {
        onSuccess: () => {
          showToast(t('admin.footerContent.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.footerContent.saveError'), 'error')
        },
      },
    )
  }

  return {
    draft,
    setSocial: (social) => {
      setDraft((prev) => ({ ...prev, social }))
    },
    setLegalLinks: (legalLinks) => {
      setDraft((prev) => ({ ...prev, legalLinks }))
    },
    setShowPoweredBy: (showPoweredBy) => {
      setDraft((prev) => ({ ...prev, showPoweredBy }))
    },
    setCta: (patch) => {
      setDraft((prev) => ({ ...prev, cta: { ...prev.cta, ...patch } }))
    },
    setBanners: (banners) => {
      setDraft((prev) => ({ ...prev, banners }))
    },
    save,
    isLoading: settingsQuery.isLoading,
    isSaving: updateSetting.isPending,
    isDirty: JSON.stringify(draft) !== storedKey,
  }
}

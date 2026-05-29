import type { ReactNode } from 'react'
import { currentUserHasCapability } from '@/entities/auth'
import {
  AppearanceView,
  PermalinkSettingsView,
  usePermalinkSettingsPage,
} from '@/features/manage-appearance'
import { ManageSiteSettingsView, useManageSiteSettingsPage } from '@/features/manage-settings'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

/** Console redesign §06 — small chrome eyebrow that heads each settings section. */
function SectionHeader({ children }: { children: ReactNode }) {
  return (
    <p className="font-chrome text-tiny font-bold uppercase tracking-widest text-text-muted">
      {children}
    </p>
  )
}

export function SiteSettingsPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const settingsPage = useManageSiteSettingsPage()
  const permalinkPage = usePermalinkSettingsPage()

  return (
    <Stack gap="lg">
      <Stack gap="xs">
        <p className="font-chrome text-tiny font-bold uppercase tracking-widest text-accent">
          {t('admin.settings.eyebrow')}
        </p>
        <Text as="h1" variant="heading-md">
          {t('admin.settings.pageTitle')}
        </Text>
        <Text muted>{t('admin.settings.description')}</Text>
      </Stack>

      <Stack gap="sm">
        <SectionHeader>{t('admin.settings.appearance.title')}</SectionHeader>
        <AppearanceView />
      </Stack>

      <Stack gap="sm">
        <SectionHeader>{t('admin.settings.site.title')}</SectionHeader>
        <ManageSiteSettingsView {...settingsPage} canManageSettings={canManageSettings} />
      </Stack>

      <Stack gap="sm">
        <SectionHeader>{t('admin.settings.permalink.title')}</SectionHeader>
        <Text muted variant="caption">
          {t('admin.settings.permalink.description')}
        </Text>
        <PermalinkSettingsView {...permalinkPage} />
      </Stack>
    </Stack>
  )
}

import { currentUserHasCapability } from '@/entities/auth'
import {
  AppearanceView,
  PermalinkSettingsView,
  usePermalinkSettingsPage,
} from '@/features/manage-appearance'
import { ManageSiteSettingsView, useManageSiteSettingsPage } from '@/features/manage-settings'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function SiteSettingsPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const settingsPage = useManageSiteSettingsPage()
  const permalinkPage = usePermalinkSettingsPage()

  return (
    <Stack gap="lg">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('admin.settings.pageTitle')}
        </Text>
        <Text muted>{t('admin.settings.description')}</Text>
      </Stack>
      <AppearanceView />
      <ManageSiteSettingsView {...settingsPage} canManageSettings={canManageSettings} />
      <PermalinkSettingsView {...permalinkPage} />
    </Stack>
  )
}

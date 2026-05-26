import { ManageSiteSettingsView } from '@/features/manage-settings'
import { AppearanceView, PermalinkSettingsView } from '@/features/manage-appearance'
import { currentUserHasCapability } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function SiteSettingsPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  return (
    <Stack gap="lg">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('admin.settings.pageTitle')}
        </Text>
        <Text muted>{t('admin.settings.description')}</Text>
      </Stack>
      <AppearanceView />
      <ManageSiteSettingsView canManageSettings={canManageSettings} />
      <PermalinkSettingsView />
    </Stack>
  )
}

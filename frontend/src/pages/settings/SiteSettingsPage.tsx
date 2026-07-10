import { currentUserHasCapability } from '@/entities/auth'
import {
  AppearanceView,
  PermalinkSettingsView,
  usePermalinkSettingsPage,
} from '@/features/manage-appearance'
import { FrontPageView, useFrontPage } from '@/features/manage-front-page'
import { RecordPageDisplayView, useRecordPageDisplay } from '@/features/manage-record-page'
import { ManageSiteSettingsView, useManageSiteSettingsPage } from '@/features/manage-settings'
import { useTranslation } from '@/shared/i18n'
import { PageHeader, SectionHeader, Stack, Text } from '@/shared/ui'

export function SiteSettingsPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const frontPage = useFrontPage()
  const recordPage = useRecordPageDisplay()
  const settingsPage = useManageSiteSettingsPage()
  const permalinkPage = usePermalinkSettingsPage()

  return (
    <Stack gap="lg">
      <PageHeader
        eyebrow={t('admin.settings.eyebrow')}
        title={t('admin.settings.pageTitle')}
        description={t('admin.settings.description')}
      />

      <Stack gap="sm">
        <SectionHeader>{t('admin.settings.frontPage.title')}</SectionHeader>
        <FrontPageView {...frontPage} />
      </Stack>

      <Stack gap="sm">
        <SectionHeader>{t('admin.settings.recordPage.title')}</SectionHeader>
        <RecordPageDisplayView {...recordPage} />
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

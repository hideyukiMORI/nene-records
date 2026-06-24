import { WxrImportView } from '@/features/import-wxr'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function ImportPage() {
  const { t } = useTranslation()

  return (
    <Stack gap="lg">
      <Stack gap="xs">
        <Text as="h1" variant="heading-md">
          {t('admin.import.pageTitle')}
        </Text>
        <Text muted>{t('admin.import.pageDescription')}</Text>
      </Stack>
      <WxrImportView />
    </Stack>
  )
}

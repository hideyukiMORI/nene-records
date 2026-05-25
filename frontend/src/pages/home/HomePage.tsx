import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function HomePage() {
  const { t } = useTranslation()

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.home.title')}
      </Text>
      <Text muted>{t('admin.home.description')}</Text>
      <Link to="/view" className="text-body font-medium text-accent hover:text-accent-hover">
        {t('admin.home.openPublicSite')}
      </Link>
    </Stack>
  )
}

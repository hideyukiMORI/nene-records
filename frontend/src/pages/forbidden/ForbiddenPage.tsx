import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

export function ForbiddenPage() {
  const { t } = useTranslation()

  return (
    <Stack gap="md" className="py-stack-xl">
      <Text as="h1" variant="heading-md">
        {t('admin.forbidden.title')}
      </Text>
      <Text muted>{t('admin.forbidden.description')}</Text>
      <Stack direction="horizontal" gap="sm">
        <Link to="/">
          <Button variant="secondary">{t('common.actions.backToHome')}</Button>
        </Link>
      </Stack>
    </Stack>
  )
}

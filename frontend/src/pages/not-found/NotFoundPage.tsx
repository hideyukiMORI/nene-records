import { Link, useRouteError } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

export function NotFoundPage() {
  const { t } = useTranslation()
  const error = useRouteError()
  const is404 =
    error !== null &&
    typeof error === 'object' &&
    'status' in error &&
    (error as { status: number }).status === 404

  return (
    <Stack gap="md" className="py-stack-xl">
      <Text as="h1" variant="heading-md">
        {is404 ? t('admin.notFound.title') : t('admin.notFound.errorTitle')}
      </Text>
      <Text muted>
        {is404 ? t('admin.notFound.description') : t('admin.notFound.errorDescription')}
      </Text>
      <Stack direction="horizontal" gap="sm">
        <Link to="/">
          <Button variant="secondary">{t('common.actions.backToHome')}</Button>
        </Link>
      </Stack>
    </Stack>
  )
}

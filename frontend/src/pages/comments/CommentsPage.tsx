import { Navigate } from 'react-router-dom'
import { currentUserHasCapability } from '@/entities/auth'
import { ManageCommentsView, useManageCommentsPage } from '@/features/manage-comments'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function CommentsPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const commentsPage = useManageCommentsPage()

  if (!canManageSettings) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.comments.pageTitle')}
      </Text>
      <Text muted>{t('admin.comments.description')}</Text>
      <ManageCommentsView {...commentsPage} />
    </Stack>
  )
}

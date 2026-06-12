import { Navigate } from 'react-router-dom'
import { currentUserHasCapability } from '@/entities/auth'
import { ManageCommentsView, useManageCommentsPage } from '@/features/manage-comments'
import { useTranslation } from '@/shared/i18n'
import { PageHeader, Stack } from '@/shared/ui'

export function CommentsPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const commentsPage = useManageCommentsPage()

  if (!canManageSettings) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <PageHeader
        title={t('admin.comments.pageTitle')}
        description={t('admin.comments.description')}
      />
      <ManageCommentsView {...commentsPage} />
    </Stack>
  )
}

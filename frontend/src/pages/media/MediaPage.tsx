import { Navigate } from 'react-router-dom'
import { currentUserHasCapability } from '@/entities/auth'
import { MediaLibraryView, useMediaLibraryPage } from '@/features/media-library'
import { useTranslation } from '@/shared/i18n'
import { PageHeader, Stack } from '@/shared/ui'

export function MediaPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const page = useMediaLibraryPage()

  if (!canManageSettings) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <PageHeader title={t('admin.media.pageTitle')} description={t('admin.media.description')} />
      <MediaLibraryView
        items={page.items}
        isLoading={page.isLoading}
        isError={page.isError}
        errorTitle={page.errorTitle}
        isUploading={page.isUploading}
        uploadErrorTitle={page.uploadErrorTitle}
        onUpload={page.uploadFiles}
        copiedId={page.copiedId}
        onCopy={page.copyUrl}
        onUpdateAlt={page.updateAlt}
        deleteTarget={page.deleteTarget}
        isDeleting={page.isDeleting}
        usages={page.usages}
        hasUsages={page.hasUsages}
        isLoadingUsages={page.isLoadingUsages}
        onRequestDelete={page.requestDelete}
        onCancelDelete={page.cancelDelete}
        onConfirmDelete={page.confirmDelete}
        onRetry={() => {
          void page.refetch()
        }}
      />
    </Stack>
  )
}

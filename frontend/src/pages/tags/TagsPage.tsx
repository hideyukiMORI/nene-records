import { Navigate } from 'react-router-dom'
import { ManageTagsView, useManageTagsPage } from '@/features/manage-tags'
import { currentUserHasCapability } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function TagsPage() {
  const { t } = useTranslation()
  const canManageTags = currentUserHasCapability('manage_tags')
  const {
    items,
    isLoading,
    isError,
    errorTitle,
    refetch,
    createTag,
    isCreating,
    createErrorTitle,
    editTarget,
    requestEdit,
    cancelEdit,
    updateTag,
    isUpdating,
    updateErrorTitle,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting,
  } = useManageTagsPage()

  if (!canManageTags) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.tags.pageTitle')}
      </Text>
      <ManageTagsView
        items={items}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        isCreating={isCreating}
        createErrorTitle={createErrorTitle}
        editTarget={editTarget}
        isUpdating={isUpdating}
        updateErrorTitle={updateErrorTitle}
        deleteTarget={deleteTarget}
        isDeleting={isDeleting}
        onRetry={() => {
          void refetch()
        }}
        onCreate={createTag}
        onRequestEdit={requestEdit}
        onCancelEdit={cancelEdit}
        onUpdate={updateTag}
        onRequestDelete={requestDelete}
        onCancelDelete={cancelDelete}
        onConfirmDelete={confirmDelete}
      />
    </Stack>
  )
}

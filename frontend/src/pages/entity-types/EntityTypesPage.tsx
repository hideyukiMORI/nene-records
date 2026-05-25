import { ManageEntityTypesView, useManageEntityTypesPage } from '@/features/manage-entity-types'
import { currentUserHasCapability } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function EntityTypesPage() {
  const { t } = useTranslation()
  const canManageSchema = currentUserHasCapability('manage_schema')
  const {
    items,
    isLoading,
    isError,
    errorTitle,
    refetch,
    createEntityType,
    isCreating,
    createErrorTitle,
    editTarget,
    requestEdit,
    cancelEdit,
    updateEntityType,
    isUpdating,
    updateErrorTitle,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting,
    deleteErrorDetail,
  } = useManageEntityTypesPage()

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.entityTypes.pageTitle')}
      </Text>
      <ManageEntityTypesView
        items={items}
        canManageSchema={canManageSchema}
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
        deleteErrorDetail={deleteErrorDetail}
        onRetry={() => {
          void refetch()
        }}
        onCreate={createEntityType}
        onRequestEdit={requestEdit}
        onCancelEdit={cancelEdit}
        onUpdate={updateEntityType}
        onRequestDelete={requestDelete}
        onCancelDelete={cancelDelete}
        onConfirmDelete={confirmDelete}
      />
    </Stack>
  )
}

import { ManageEntityTypesView, useManageEntityTypesPage } from '@/features/manage-entity-types'
import { currentUserHasCapability } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { PageHeader, Stack } from '@/shared/ui'

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
    moveEntityType,
    isReordering,
  } = useManageEntityTypesPage()

  return (
    <Stack gap="md">
      <PageHeader title={t('admin.entityTypes.pageTitle')} />
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
        isReordering={isReordering}
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
        onMove={(entityType, direction) => {
          void moveEntityType(entityType.id, direction)
        }}
      />
    </Stack>
  )
}

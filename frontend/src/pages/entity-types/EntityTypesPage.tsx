import { ManageEntityTypesView, useManageEntityTypesPage } from '@/features/manage-entity-types'
import { Stack, Text } from '@/shared/ui'

export function EntityTypesPage() {
  const {
    items,
    isLoading,
    isError,
    errorTitle,
    refetch,
    createEntityType,
    isCreating,
    createErrorTitle,
    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting,
  } = useManageEntityTypesPage()

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        Entity types
      </Text>
      <ManageEntityTypesView
        items={items}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        isCreating={isCreating}
        createErrorTitle={createErrorTitle}
        deleteTarget={deleteTarget}
        isDeleting={isDeleting}
        onRetry={() => {
          void refetch()
        }}
        onCreate={createEntityType}
        onRequestDelete={requestDelete}
        onCancelDelete={cancelDelete}
        onConfirmDelete={confirmDelete}
      />
    </Stack>
  )
}

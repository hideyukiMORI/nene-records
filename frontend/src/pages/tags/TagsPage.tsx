import { ManageTagsView, useManageTagsPage } from '@/features/manage-tags'
import { Stack, Text } from '@/shared/ui'

export function TagsPage() {
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

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        Tags
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

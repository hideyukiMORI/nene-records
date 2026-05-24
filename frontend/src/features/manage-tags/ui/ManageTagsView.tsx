import type { Tag } from '@/entities/tag'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { TagCreateForm } from './TagCreateForm'
import { TagEditForm } from './TagEditForm'
import { TagListPanel } from './TagListPanel'

export interface ManageTagsViewProps {
  items: Tag[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isCreating: boolean
  createErrorTitle: string | null
  editTarget: Tag | null
  isUpdating: boolean
  updateErrorTitle: string | null
  deleteTarget: Tag | null
  isDeleting: boolean
  onRetry: () => void
  onCreate: (values: { name: string; slug: string }) => Promise<void>
  onRequestEdit: (tag: Tag) => void
  onCancelEdit: () => void
  onUpdate: (values: { name: string; slug: string }) => Promise<void>
  onRequestDelete: (tag: Tag) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageTagsView({
  items,
  isLoading,
  isError,
  errorTitle,
  isCreating,
  createErrorTitle,
  editTarget,
  isUpdating,
  updateErrorTitle,
  deleteTarget,
  isDeleting,
  onRetry,
  onCreate,
  onRequestEdit,
  onCancelEdit,
  onUpdate,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
}: ManageTagsViewProps) {
  return (
    <>
      <Stack gap="lg">
        <TagCreateForm
          isSubmitting={isCreating}
          serverErrorTitle={createErrorTitle}
          onSubmit={onCreate}
        />
        {editTarget !== null ? (
          <TagEditForm
            tag={editTarget}
            isSubmitting={isUpdating}
            serverErrorTitle={updateErrorTitle}
            onSubmit={onUpdate}
            onCancel={onCancelEdit}
          />
        ) : null}
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            Existing tags
          </Text>
          <TagListPanel
            items={items}
            isLoading={isLoading}
            isError={isError}
            errorTitle={errorTitle}
            isDeleting={isDeleting}
            onRetry={onRetry}
            onEdit={onRequestEdit}
            onDelete={onRequestDelete}
          />
        </Stack>
      </Stack>
      <ConfirmDialog
        open={deleteTarget !== null}
        title="Delete tag?"
        description={
          deleteTarget !== null
            ? `"${deleteTarget.name}" will be removed. Attached records keep their data but lose this tag.`
            : undefined
        }
        confirmLabel={isDeleting ? 'Deleting…' : 'Delete'}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}

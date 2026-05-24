import type { EntityType } from '@/entities/entity-type'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { EntityTypeCreateForm } from './EntityTypeCreateForm'
import { EntityTypeEditForm } from './EntityTypeEditForm'
import { EntityTypeListPanel } from './EntityTypeListPanel'

export interface ManageEntityTypesViewProps {
  items: EntityType[]
  canManageSchema: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isCreating: boolean
  createErrorTitle: string | null
  editTarget: EntityType | null
  isUpdating: boolean
  updateErrorTitle: string | null
  deleteTarget: EntityType | null
  isDeleting: boolean
  deleteErrorDetail: string | null
  onRetry: () => void
  onCreate: (values: { name: string; slug: string }) => Promise<void>
  onRequestEdit: (entityType: EntityType) => void
  onCancelEdit: () => void
  onUpdate: (values: { name: string; slug: string }) => Promise<void>
  onRequestDelete: (entityType: EntityType) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageEntityTypesView({
  items,
  canManageSchema,
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
  deleteErrorDetail,
  onRetry,
  onCreate,
  onRequestEdit,
  onCancelEdit,
  onUpdate,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
}: ManageEntityTypesViewProps) {
  return (
    <>
      <Stack gap="lg">
        {canManageSchema ? (
          <EntityTypeCreateForm
            isSubmitting={isCreating}
            serverErrorTitle={createErrorTitle}
            onSubmit={onCreate}
          />
        ) : null}
        {canManageSchema && editTarget !== null ? (
          <EntityTypeEditForm
            entityType={editTarget}
            isSubmitting={isUpdating}
            serverErrorTitle={updateErrorTitle}
            onSubmit={onUpdate}
            onCancel={onCancelEdit}
          />
        ) : null}
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            Existing types
          </Text>
          <EntityTypeListPanel
            items={items}
            canManageSchema={canManageSchema}
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
        title="Delete entity type?"
        description={
          deleteTarget !== null
            ? `"${deleteTarget.name}" will be removed. This cannot be undone.`
            : undefined
        }
        errorDetail={deleteErrorDetail}
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

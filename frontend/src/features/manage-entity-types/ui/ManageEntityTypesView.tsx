import type { EntityType } from '@/entities/entity-type'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { EntityTypeCreateForm } from './EntityTypeCreateForm'
import { EntityTypeListPanel } from './EntityTypeListPanel'

export interface ManageEntityTypesViewProps {
  items: EntityType[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isCreating: boolean
  createErrorTitle: string | null
  deleteTarget: EntityType | null
  isDeleting: boolean
  onRetry: () => void
  onCreate: (values: { name: string; slug: string }) => Promise<void>
  onRequestDelete: (entityType: EntityType) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageEntityTypesView({
  items,
  isLoading,
  isError,
  errorTitle,
  isCreating,
  createErrorTitle,
  deleteTarget,
  isDeleting,
  onRetry,
  onCreate,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
}: ManageEntityTypesViewProps) {
  return (
    <>
      <Stack gap="lg">
        <EntityTypeCreateForm
          isSubmitting={isCreating}
          serverErrorTitle={createErrorTitle}
          onSubmit={onCreate}
        />
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            Existing types
          </Text>
          <EntityTypeListPanel
            items={items}
            isLoading={isLoading}
            isError={isError}
            errorTitle={errorTitle}
            isDeleting={isDeleting}
            onRetry={onRetry}
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

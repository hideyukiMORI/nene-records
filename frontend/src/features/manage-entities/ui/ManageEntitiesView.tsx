import type { Entity } from '@/entities/entity'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { EntityCreatePanel } from './EntityCreatePanel'
import { EntityListPanel } from './EntityListPanel'

export interface ManageEntitiesViewProps {
  entityTypeId: number
  entityTypeName: string | null
  entityTypeSlug: string | null
  items: Entity[]
  total: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isCreating: boolean
  createErrorTitle: string | null
  deleteTarget: Entity | null
  isDeleting: boolean
  onRetry: () => void
  onCreate: () => Promise<void>
  onRequestDelete: (entity: Entity) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageEntitiesView({
  entityTypeId,
  entityTypeName,
  entityTypeSlug,
  items,
  total,
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
}: ManageEntitiesViewProps) {
  return (
    <>
      <Stack gap="lg">
        <Stack gap="xs">
          <Text as="p" muted>
            {entityTypeSlug ?? '…'}
          </Text>
          <Text as="p" muted>
            {total} record{total === 1 ? '' : 's'}
          </Text>
        </Stack>
        <EntityCreatePanel
          isSubmitting={isCreating}
          serverErrorTitle={createErrorTitle}
          onCreate={onCreate}
        />
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {entityTypeName !== null ? `${entityTypeName} records` : 'Records'}
          </Text>
          <EntityListPanel
            entityTypeId={entityTypeId}
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
        title="Delete record?"
        description={
          deleteTarget !== null
            ? `Record #${String(deleteTarget.id)} will be soft-deleted.`
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

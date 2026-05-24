import type { FieldDataType, FieldDef } from '@/entities/field-def'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { FieldDefCreateForm } from './FieldDefCreateForm'
import { FieldDefEditForm } from './FieldDefEditForm'
import { FieldDefListPanel } from './FieldDefListPanel'

export interface ManageFieldDefsViewProps {
  entityTypeSlug: string | null
  items: FieldDef[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isCreating: boolean
  createErrorTitle: string | null
  editTarget: FieldDef | null
  isUpdating: boolean
  updateErrorTitle: string | null
  deleteTarget: FieldDef | null
  isDeleting: boolean
  onRetry: () => void
  onCreate: (values: { fieldKey: string; dataType: FieldDataType }) => Promise<void>
  onRequestEdit: (fieldDef: FieldDef) => void
  onCancelEdit: () => void
  onUpdate: (values: { fieldKey: string; dataType: FieldDataType }) => Promise<void>
  onRequestDelete: (fieldDef: FieldDef) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageFieldDefsView({
  entityTypeSlug,
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
}: ManageFieldDefsViewProps) {
  return (
    <>
      <Stack gap="lg">
        {entityTypeSlug !== null ? (
          <Text as="p" muted>
            Schema for {entityTypeSlug}
          </Text>
        ) : null}
        <FieldDefCreateForm
          isSubmitting={isCreating}
          serverErrorTitle={createErrorTitle}
          onSubmit={onCreate}
        />
        {editTarget !== null ? (
          <FieldDefEditForm
            fieldDef={editTarget}
            isSubmitting={isUpdating}
            serverErrorTitle={updateErrorTitle}
            onSubmit={onUpdate}
            onCancel={onCancelEdit}
          />
        ) : null}
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            Field definitions
          </Text>
          <FieldDefListPanel
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
        title="Delete field?"
        description={
          deleteTarget !== null
            ? `"${deleteTarget.fieldKey}" will be removed from the schema.`
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

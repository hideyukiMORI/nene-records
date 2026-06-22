import type { FieldDef } from '@/entities/field-def'
import { useTranslation } from '@/shared/i18n'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import type { CreateFieldDefFormValues } from '../hooks/use-create-field-def-form'
import { FieldDefCreateForm } from './FieldDefCreateForm'
import { FieldDefEditForm } from './FieldDefEditForm'
import { FieldDefListPanel } from './FieldDefListPanel'

export interface ManageFieldDefsViewProps {
  entityTypeSlug: string | null
  canManageSchema: boolean
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
  onCreate: (values: CreateFieldDefFormValues) => Promise<void>
  onRequestEdit: (fieldDef: FieldDef) => void
  onCancelEdit: () => void
  onUpdate: (values: CreateFieldDefFormValues) => Promise<void>
  onRequestDelete: (fieldDef: FieldDef) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageFieldDefsView({
  entityTypeSlug,
  canManageSchema,
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
  const { t } = useTranslation()

  return (
    <>
      <Stack gap="lg">
        {entityTypeSlug !== null ? (
          <Text as="p" muted>
            {t('admin.fieldDefs.schemaFor', { slug: entityTypeSlug })}
          </Text>
        ) : null}
        {canManageSchema ? (
          <FieldDefCreateForm
            isSubmitting={isCreating}
            serverErrorTitle={createErrorTitle}
            onSubmit={onCreate}
          />
        ) : null}
        {canManageSchema && editTarget !== null ? (
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
            {t('admin.fieldDefs.list.title')}
          </Text>
          <FieldDefListPanel
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
        title={t('admin.fieldDefs.delete.title')}
        description={
          deleteTarget !== null
            ? t('admin.fieldDefs.delete.description', { fieldKey: deleteTarget.fieldKey })
            : undefined
        }
        confirmLabel={isDeleting ? t('common.actions.deleting') : t('common.actions.delete')}
        cancelLabel={t('common.actions.cancel')}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}

import type { Tag } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { ConfirmDialog, SectionHeader, Stack } from '@/shared/ui'
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
  const { t } = useTranslation()

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
          <SectionHeader>{t('admin.tags.list.title')}</SectionHeader>
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
        title={t('admin.tags.delete.title')}
        description={
          deleteTarget !== null
            ? t('admin.tags.delete.description', { name: deleteTarget.name })
            : undefined
        }
        confirmLabel={isDeleting ? t('common.actions.deleting') : t('common.actions.delete')}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}

import type { NavigationItem } from '@/entities/navigation-item'
import { useTranslation } from '@/shared/i18n'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import type { NavigationItemFormValues } from '../hooks/use-manage-navigation-page'
import { NavigationItemCreateForm } from './NavigationItemCreateForm'
import { NavigationItemEditForm } from './NavigationItemEditForm'
import { NavigationItemListPanel } from './NavigationItemListPanel'

export interface ManageNavigationViewProps {
  items: NavigationItem[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isCreating: boolean
  createErrorTitle: string | null
  editTarget: NavigationItem | null
  isUpdating: boolean
  updateErrorTitle: string | null
  deleteTarget: NavigationItem | null
  isDeleting: boolean
  onRetry: () => void
  onCreate: (values: NavigationItemFormValues) => Promise<void>
  onRequestEdit: (item: NavigationItem) => void
  onCancelEdit: () => void
  onUpdate: (values: NavigationItemFormValues) => Promise<void>
  onRequestDelete: (item: NavigationItem) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>
}

export function ManageNavigationView({
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
}: ManageNavigationViewProps) {
  const { t } = useTranslation()

  return (
    <>
      <Stack gap="lg">
        <NavigationItemCreateForm
          isSubmitting={isCreating}
          serverErrorTitle={createErrorTitle}
          onSubmit={onCreate}
        />
        {editTarget !== null ? (
          <NavigationItemEditForm
            item={editTarget}
            isSubmitting={isUpdating}
            serverErrorTitle={updateErrorTitle}
            onSubmit={onUpdate}
            onCancel={onCancelEdit}
          />
        ) : null}
        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {t('admin.navigation.pageTitle')}
          </Text>
          <NavigationItemListPanel
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
        title={t('admin.navigation.delete')}
        description={deleteTarget !== null ? `"${deleteTarget.label}" を削除しますか？` : undefined}
        confirmLabel={isDeleting ? t('admin.navigation.deleting') : t('admin.navigation.delete')}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}

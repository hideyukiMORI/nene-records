import { Navigate } from 'react-router-dom'
import { currentUserHasCapability } from '@/entities/auth'
import { useManageNavigationPage, ManageNavigationView } from '@/features/manage-navigation'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function NavigationPage() {
  const { t } = useTranslation()
  const canManageSettings = currentUserHasCapability('manage_settings')
  const page = useManageNavigationPage()

  if (!canManageSettings) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.navigation.pageTitle')}
      </Text>
      <Text muted>{t('admin.navigation.description')}</Text>
      <ManageNavigationView
        items={page.items}
        isLoading={page.isLoading}
        isError={page.isError}
        errorTitle={page.errorTitle}
        isCreating={page.isCreating}
        createErrorTitle={page.createErrorTitle}
        editTarget={page.editTarget}
        isUpdating={page.isUpdating}
        updateErrorTitle={page.updateErrorTitle}
        deleteTarget={page.deleteTarget}
        isDeleting={page.isDeleting}
        onRetry={() => {
          void page.refetch()
        }}
        onCreate={page.createItem}
        onRequestEdit={page.requestEdit}
        onCancelEdit={page.cancelEdit}
        onUpdate={page.updateItem}
        onRequestDelete={page.requestDelete}
        onCancelDelete={page.cancelDelete}
        onConfirmDelete={page.confirmDelete}
      />
    </Stack>
  )
}

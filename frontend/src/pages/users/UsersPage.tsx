import { Navigate } from 'react-router-dom'
import { authStore, currentUserIsAdmin } from '@/entities/auth'
import { ManageUsersView, useManageUsersPage } from '@/features/manage-users'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export function UsersPage() {
  const { t } = useTranslation()
  const isAdmin = currentUserIsAdmin()
  const session = authStore.getSession()
  const page = useManageUsersPage()

  if (!isAdmin) {
    return <Navigate to="/forbidden" replace />
  }

  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        {t('admin.users.pageTitle')}
      </Text>
      <Text muted>{t('admin.users.description')}</Text>
      <ManageUsersView
        users={page.users}
        isLoading={page.isLoading}
        isError={page.isError}
        errorTitle={page.errorTitle}
        currentUserEmail={session?.email ?? null}
        showInviteForm={page.showInviteForm}
        isInviting={page.isInviting}
        inviteErrorTitle={page.inviteErrorTitle}
        onOpenInviteForm={page.openInviteForm}
        onCloseInviteForm={page.closeInviteForm}
        onInvite={page.inviteUser}
        isUpdatingRole={page.isUpdatingRole}
        onChangeRole={page.updateRole}
        resetPasswordTarget={page.resetPasswordTarget}
        isResettingPassword={page.isResettingPassword}
        resetPasswordErrorTitle={page.resetPasswordErrorTitle}
        onRequestResetPassword={page.requestResetPassword}
        onCancelResetPassword={page.cancelResetPassword}
        onAdminResetPassword={page.adminResetPassword}
        showChangeOwnPassword={page.showChangeOwnPassword}
        isChangingOwnPassword={page.isChangingOwnPassword}
        changeOwnPasswordErrorTitle={page.changeOwnPasswordErrorTitle}
        onOpenChangeOwnPassword={page.openChangeOwnPassword}
        onCloseChangeOwnPassword={page.closeChangeOwnPassword}
        onChangeOwnPassword={page.changeOwnPassword}
        changeEmailTarget={page.changeEmailTarget}
        isChangingEmail={page.isChangingEmail}
        changeEmailErrorTitle={page.changeEmailErrorTitle}
        onRequestChangeEmail={page.requestChangeEmail}
        onCancelChangeEmail={page.cancelChangeEmail}
        onChangeEmail={page.changeEmail}
        deleteTarget={page.deleteTarget}
        isDeleting={page.isDeleting}
        onRequestDelete={page.requestDelete}
        onCancelDelete={page.cancelDelete}
        onConfirmDelete={page.confirmDelete}
        onRetry={() => {
          void page.refetch()
        }}
      />
    </Stack>
  )
}

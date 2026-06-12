import { Navigate } from 'react-router-dom'
import { authStore, currentUserIsAdmin } from '@/entities/auth'
import { ManageUsersView, useManageUsersPage } from '@/features/manage-users'
import { useTranslation } from '@/shared/i18n'
import { Button, PageHeader, Stack } from '@/shared/ui'

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
      {/* ── Page head: eyebrow + title on the left, actions top-right ── */}
      <PageHeader
        eyebrow={t('admin.users.eyebrow')}
        title={t('admin.users.pageTitle')}
        description={t('admin.users.description')}
        actions={
          <>
            <Button
              variant="ghost"
              onClick={page.openChangeOwnPassword}
              disabled={page.showChangeOwnPassword}
            >
              {t('admin.users.changeOwnPassword.button')}
            </Button>
            <Button onClick={page.openInviteForm} disabled={page.showInviteForm}>
              {t('admin.users.invite.button')}
            </Button>
          </>
        }
      />
      <ManageUsersView
        users={page.users}
        isLoading={page.isLoading}
        isError={page.isError}
        errorTitle={page.errorTitle}
        currentUserEmail={session?.email ?? null}
        showInviteForm={page.showInviteForm}
        isInviting={page.isInviting}
        inviteErrorTitle={page.inviteErrorTitle}
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
        onCloseChangeOwnPassword={page.closeChangeOwnPassword}
        onChangeOwnPassword={page.changeOwnPassword}
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

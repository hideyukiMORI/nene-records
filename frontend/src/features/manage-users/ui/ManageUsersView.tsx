import type { User, UserRole } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { ConfirmDialog, SectionHeader, Stack } from '@/shared/ui'
import type {
  AdminResetPasswordFormValues,
  ChangeOwnPasswordFormValues,
  InviteFormValues,
} from '../hooks/use-manage-users-page'
import { AdminResetPasswordForm } from './AdminResetPasswordForm'
import { ChangeOwnPasswordForm } from './ChangeOwnPasswordForm'
import { UserInviteForm } from './UserInviteForm'
import { UserListPanel } from './UserListPanel'

export interface ManageUsersViewProps {
  users: User[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  currentUserEmail: string | null

  showInviteForm: boolean
  isInviting: boolean
  inviteErrorTitle: string | null
  onCloseInviteForm: () => void
  onInvite: (values: InviteFormValues) => Promise<void>

  isUpdatingRole: boolean
  onChangeRole: (user: User, role: UserRole) => Promise<void>

  resetPasswordTarget: User | null
  isResettingPassword: boolean
  resetPasswordErrorTitle: string | null
  onRequestResetPassword: (user: User) => void
  onCancelResetPassword: () => void
  onAdminResetPassword: (values: AdminResetPasswordFormValues) => Promise<void>

  showChangeOwnPassword: boolean
  isChangingOwnPassword: boolean
  changeOwnPasswordErrorTitle: string | null
  onCloseChangeOwnPassword: () => void
  onChangeOwnPassword: (values: ChangeOwnPasswordFormValues) => Promise<void>

  deleteTarget: User | null
  isDeleting: boolean
  onRequestDelete: (user: User) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>

  onRetry: () => void
}

export function ManageUsersView({
  users,
  isLoading,
  isError,
  errorTitle,
  currentUserEmail,

  showInviteForm,
  isInviting,
  inviteErrorTitle,
  onCloseInviteForm,
  onInvite,

  isUpdatingRole,
  onChangeRole,

  resetPasswordTarget,
  isResettingPassword,
  resetPasswordErrorTitle,
  onRequestResetPassword,
  onCancelResetPassword,
  onAdminResetPassword,

  showChangeOwnPassword,
  isChangingOwnPassword,
  changeOwnPasswordErrorTitle,
  onCloseChangeOwnPassword,
  onChangeOwnPassword,

  deleteTarget,
  isDeleting,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,

  onRetry,
}: ManageUsersViewProps) {
  const { t } = useTranslation()

  return (
    <>
      <Stack gap="lg">
        {/* Invite form */}
        {showInviteForm ? (
          <UserInviteForm
            isSubmitting={isInviting}
            serverErrorTitle={inviteErrorTitle}
            onSubmit={onInvite}
            onCancel={onCloseInviteForm}
          />
        ) : null}

        {/* Change own password form */}
        {showChangeOwnPassword ? (
          <ChangeOwnPasswordForm
            isSubmitting={isChangingOwnPassword}
            serverErrorTitle={changeOwnPasswordErrorTitle}
            onSubmit={onChangeOwnPassword}
            onCancel={onCloseChangeOwnPassword}
          />
        ) : null}

        {/* Admin reset password form */}
        {resetPasswordTarget !== null ? (
          <AdminResetPasswordForm
            user={resetPasswordTarget}
            isSubmitting={isResettingPassword}
            serverErrorTitle={resetPasswordErrorTitle}
            onSubmit={onAdminResetPassword}
            onCancel={onCancelResetPassword}
          />
        ) : null}

        {/* User list */}
        <Stack gap="sm">
          <SectionHeader>{t('admin.users.list.title')}</SectionHeader>
          <UserListPanel
            users={users}
            isLoading={isLoading || isUpdatingRole}
            isError={isError}
            errorTitle={errorTitle}
            currentUserEmail={currentUserEmail}
            onRetry={onRetry}
            onChangeRole={onChangeRole}
            onResetPassword={onRequestResetPassword}
            onDelete={onRequestDelete}
          />
        </Stack>
      </Stack>

      {/* Delete confirm dialog */}
      <ConfirmDialog
        open={deleteTarget !== null}
        title={t('admin.users.delete.confirmTitle')}
        description={
          deleteTarget !== null
            ? t('admin.users.delete.confirmDescription', { email: deleteTarget.email })
            : undefined
        }
        confirmLabel={isDeleting ? t('admin.users.delete.deleting') : t('admin.users.delete')}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}

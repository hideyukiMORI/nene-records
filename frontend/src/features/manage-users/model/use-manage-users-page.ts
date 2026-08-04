import { useCallback, useState } from 'react'
import {
  useAdminResetPassword,
  useChangeOwnPassword,
  useDeleteUser,
  useInviteUser,
  useUpdateUserRole,
  useUserList,
  type User,
  type UserRole,
} from '@/entities/user'

export interface InviteFormValues {
  email: string
  role: UserRole
}

export interface ChangeOwnPasswordFormValues {
  currentPassword: string
  newPassword: string
}

export interface AdminResetPasswordFormValues {
  newPassword: string
}

export function useManageUsersPage() {
  const listQuery = useUserList()
  const inviteMutation = useInviteUser()
  const updateRoleMutation = useUpdateUserRole()
  const adminResetPasswordMutation = useAdminResetPassword()
  const changeOwnPasswordMutation = useChangeOwnPassword()
  const deleteMutation = useDeleteUser()

  const [deleteTarget, setDeleteTarget] = useState<User | null>(null)
  const [resetPasswordTarget, setResetPasswordTarget] = useState<User | null>(null)
  const [showInviteForm, setShowInviteForm] = useState(false)
  const [showChangeOwnPassword, setShowChangeOwnPassword] = useState(false)

  const inviteUser = useCallback(
    async (values: InviteFormValues) => {
      await inviteMutation.mutateAsync({ email: values.email, role: values.role })
      setShowInviteForm(false)
    },
    [inviteMutation],
  )

  const updateRole = useCallback(
    async (id: number, role: UserRole) => {
      await updateRoleMutation.mutateAsync({ id, input: { role } })
    },
    [updateRoleMutation],
  )

  const adminResetPassword = useCallback(
    async (values: AdminResetPasswordFormValues) => {
      if (resetPasswordTarget === null) return
      await adminResetPasswordMutation.mutateAsync({
        id: resetPasswordTarget.id,
        input: { newPassword: values.newPassword },
      })
      setResetPasswordTarget(null)
    },
    [adminResetPasswordMutation, resetPasswordTarget],
  )

  const changeOwnPassword = useCallback(
    async (values: ChangeOwnPasswordFormValues) => {
      await changeOwnPasswordMutation.mutateAsync({
        currentPassword: values.currentPassword,
        newPassword: values.newPassword,
      })
      setShowChangeOwnPassword(false)
    },
    [changeOwnPasswordMutation],
  )

  const requestDelete = useCallback((user: User) => {
    setDeleteTarget(user)
  }, [])

  const cancelDelete = useCallback(() => {
    setDeleteTarget(null)
  }, [])

  const confirmDelete = useCallback(async () => {
    if (deleteTarget === null) return
    await deleteMutation.mutateAsync(deleteTarget.id)
    setDeleteTarget(null)
  }, [deleteMutation, deleteTarget])

  const requestResetPassword = useCallback((user: User) => {
    setResetPasswordTarget(user)
  }, [])

  const cancelResetPassword = useCallback(() => {
    setResetPasswordTarget(null)
  }, [])

  return {
    users: listQuery.data?.items ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    errorTitle: listQuery.error?.title ?? null,
    refetch: listQuery.refetch,

    showInviteForm,
    openInviteForm: () => {
      setShowInviteForm(true)
    },
    closeInviteForm: () => {
      setShowInviteForm(false)
    },
    inviteUser,
    isInviting: inviteMutation.isPending,
    inviteErrorTitle: inviteMutation.error?.title ?? null,

    updateRole,
    isUpdatingRole: updateRoleMutation.isPending,

    resetPasswordTarget,
    requestResetPassword,
    cancelResetPassword,
    adminResetPassword,
    isResettingPassword: adminResetPasswordMutation.isPending,
    resetPasswordErrorTitle: adminResetPasswordMutation.error?.title ?? null,

    showChangeOwnPassword,
    openChangeOwnPassword: () => {
      setShowChangeOwnPassword(true)
    },
    closeChangeOwnPassword: () => {
      setShowChangeOwnPassword(false)
    },
    changeOwnPassword,
    isChangingOwnPassword: changeOwnPasswordMutation.isPending,
    changeOwnPasswordErrorTitle: changeOwnPasswordMutation.error?.title ?? null,

    deleteTarget,
    requestDelete,
    cancelDelete,
    confirmDelete,
    isDeleting: deleteMutation.isPending,
  }
}

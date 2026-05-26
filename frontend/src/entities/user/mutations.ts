import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { InviteUserResponseDto, UserDto } from './api-types'
import { mapUserDtoToModel } from './mapper'
import type {
  AdminResetPasswordInput,
  ChangeOwnPasswordInput,
  CreateUserInput,
  InviteUserInput,
  UpdateUserRoleInput,
  User,
} from './model'
import { userKeys } from './query-keys'

export function useCreateUser(): UseMutationResult<User, AppError, CreateUserInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<UserDto>('/api/v1/users', {
        email: input.email,
        password: input.password,
        role: input.role,
      })
      return mapUserDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: userKeys.list() })
    },
  })
}

export function useUpdateUserRole(): UseMutationResult<
  User,
  AppError,
  { id: number; input: UpdateUserRoleInput }
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({ id, input }) => {
      const dto = await apiClient.patch<UserDto>(`/api/v1/users/${String(id)}`, {
        role: input.role,
      })
      return mapUserDtoToModel(dto)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: userKeys.list() })
    },
  })
}

export function useAdminResetPassword(): UseMutationResult<
  void,
  AppError,
  { id: number; input: AdminResetPasswordInput }
> {
  return useMutation({
    mutationFn: async ({ id, input }) => {
      await apiClient.patch(`/api/v1/users/${String(id)}/password`, {
        new_password: input.newPassword,
      })
    },
  })
}

export function useChangeOwnPassword(): UseMutationResult<void, AppError, ChangeOwnPasswordInput> {
  return useMutation({
    mutationFn: async (input) => {
      await apiClient.put('/api/v1/users/me/password', {
        current_password: input.currentPassword,
        new_password: input.newPassword,
      })
    },
  })
}

export function useDeleteUser(): UseMutationResult<void, AppError, number> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (id) => {
      await apiClient.delete(`/api/v1/users/${String(id)}`)
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: userKeys.list() })
    },
  })
}

export function useInviteUser(): UseMutationResult<
  { id: number; email: string; role: string; status: string },
  AppError,
  InviteUserInput
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<InviteUserResponseDto>('/api/v1/users/invite', {
        email: input.email,
        role: input.role,
      })
      return dto
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: userKeys.list() })
    },
  })
}

export function useAcceptInvite(): UseMutationResult<
  undefined,
  AppError,
  { token: string; password: string }
> {
  return useMutation({
    mutationFn: async (input) => {
      await apiClient.post<undefined>('/api/v1/auth/accept-invite', {
        token: input.token,
        password: input.password,
      })
      return undefined
    },
  })
}

export function useConfirmPasswordReset(): UseMutationResult<
  undefined,
  AppError,
  { token: string; newPassword: string }
> {
  return useMutation({
    mutationFn: async (input) => {
      await apiClient.post<undefined>('/api/v1/auth/password-reset/confirm', {
        token: input.token,
        new_password: input.newPassword,
      })
      return undefined
    },
  })
}

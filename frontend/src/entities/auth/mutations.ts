import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { LoginRequestDto, LoginResponseDto } from './api-types'
import { authStore, type AuthSession } from './model'

export function useLogin(): UseMutationResult<AuthSession, AppError, LoginRequestDto> {
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<LoginResponseDto>('/api/v1/auth/login', input)
      const session: AuthSession = {
        token: dto.token,
        expiresAt: dto.expires_at,
        email: dto.email,
        role: dto.role,
      }
      authStore.setSession(session)
      return session
    },
  })
}

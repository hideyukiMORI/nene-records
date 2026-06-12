import { useMutation, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { LoginRequestDto, LoginResponseDto } from './api-types'
import { authStore, type AuthSession } from './model'

export function useLogin(): UseMutationResult<AuthSession, AppError, LoginRequestDto> {
  return useMutation({
    mutationFn: async (input) => {
      // The API sets the session token as an HttpOnly cookie; we only keep the
      // non-secret profile (the body token is ignored by the browser client).
      const dto = await apiClient.post<LoginResponseDto>('/api/v1/auth/login', input)
      const session: AuthSession = {
        expiresAt: dto.expires_at,
        email: dto.email,
        role: dto.role,
      }
      authStore.setSession(session)
      return session
    },
  })
}

export function useLogout(): UseMutationResult<void, AppError, void> {
  return useMutation({
    mutationFn: async () => {
      try {
        await apiClient.post('/api/v1/auth/logout', {})
      } finally {
        // Always clear the local profile, even if the network call fails.
        authStore.clearSession()
      }
    },
  })
}

import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { LoginRequestDto, LoginResponseDto } from './api-types'
import { authStore, type AuthSession } from './model'

export function useLogin(): UseMutationResult<AuthSession, AppError, LoginRequestDto> {
  const queryClient = useQueryClient()
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
    // Drop any org-scoped data cached before this session (e.g. a different
    // user/org on a shared terminal) so the new session never reads it.
    onSuccess: () => {
      queryClient.clear()
    },
  })
}

export function useLogout(): UseMutationResult<void, AppError, void> {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async () => {
      try {
        await apiClient.post('/api/v1/auth/logout', {})
      } finally {
        // Always clear the local profile, even if the network call fails.
        authStore.clearSession()
      }
    },
    // Wipe the org-scoped query cache on logout so the next user cannot read the
    // previous session's data from cache (cross-tenant residue).
    onSettled: () => {
      queryClient.clear()
    },
  })
}

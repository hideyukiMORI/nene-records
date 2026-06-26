import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { AccountResponseDto } from './api-types'
import { mapAccountResponseDtoToModel } from './mapper'
import type { Account } from './model'
import { accountKeys } from './query-keys'

/** The calling tenant's own account (plan, entitlements, usage). */
export function useAccount(): UseQueryResult<Account, AppError> {
  return useQuery({
    queryKey: accountKeys.detail(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<AccountResponseDto>('/api/v1/account', signal)
      return mapAccountResponseDtoToModel(dto)
    },
  })
}

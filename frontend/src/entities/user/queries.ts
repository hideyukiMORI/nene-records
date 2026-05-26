import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { UserListDto } from './api-types'
import { mapUserListDtoToModel } from './mapper'
import type { UserList } from './model'
import { userKeys } from './query-keys'

export function useUserList(): UseQueryResult<UserList, AppError> {
  return useQuery({
    queryKey: userKeys.list(),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<UserListDto>('/api/v1/users', signal)
      return mapUserListDtoToModel(dto)
    },
  })
}

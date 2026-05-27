import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { UserDto, UserListDto } from './api-types'
import { mapUserDtoToModel, mapUserListDtoToModel } from './mapper'
import type { User, UserList } from './model'
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

export function useUserById(id: number): UseQueryResult<User, AppError> {
  return useQuery({
    queryKey: userKeys.detail(id),
    queryFn: async ({ signal }) => {
      const dto = await apiClient.get<UserDto>(`/api/v1/users/${String(id)}`, signal)
      return mapUserDtoToModel(dto)
    },
  })
}

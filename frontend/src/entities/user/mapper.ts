import type { UserDto, UserListDto } from './api-types'
import type { User, UserList, UserRole, UserStatus } from './model'

export function mapUserDtoToModel(dto: UserDto): User {
  return {
    id: dto.id,
    email: dto.email,
    role: dto.role as UserRole,
    status: dto.status as UserStatus,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapUserListDtoToModel(dto: UserListDto): UserList {
  return {
    users: dto.items.map(mapUserDtoToModel),
  }
}

import type { UserDto, UserListDto, UserProfileDto } from './api-types'
import type { User, UserList, UserProfile, UserRole, UserStatus } from './model'

export function mapUserDtoToModel(dto: UserDto): User {
  return {
    id: dto.id,
    email: dto.email,
    role: dto.role as UserRole,
    organizationId: dto.organization_id,
    orgRole: dto.org_role,
    status: dto.status as UserStatus,
    displayName: dto.display_name,
    fullName: dto.full_name,
    jobTitle: dto.job_title,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapUserListDtoToModel(dto: UserListDto): UserList {
  return {
    items: dto.items.map(mapUserDtoToModel),
  }
}

export function mapUserProfileDtoToModel(dto: UserProfileDto): UserProfile {
  return {
    displayName: dto.display_name,
    fullName: dto.full_name,
    jobTitle: dto.job_title,
  }
}

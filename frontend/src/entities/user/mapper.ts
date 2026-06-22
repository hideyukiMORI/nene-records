import type { UserDto, UserListDto, UserProfileDto } from './api-types'
import type { User, UserList, UserProfile, UserRole } from './model'

export function mapUserDtoToModel(dto: UserDto): User {
  return {
    id: dto.id,
    email: dto.email,
    role: dto.role as UserRole,
    organizationId: dto.organization_id ?? null,
    orgRole: dto.org_role ?? null,
    status: dto.status,
    pendingEmail: dto.pending_email ?? null,
    displayName: dto.display_name ?? null,
    fullName: dto.full_name ?? null,
    jobTitle: dto.job_title ?? null,
    createdAt: dto.created_at ?? null,
    updatedAt: dto.updated_at ?? null,
  }
}

export function mapUserListDtoToModel(dto: UserListDto): UserList {
  return {
    items: dto.items.map(mapUserDtoToModel),
  }
}

export function mapUserProfileDtoToModel(dto: UserProfileDto): UserProfile {
  return {
    displayName: dto.display_name ?? null,
    fullName: dto.full_name ?? null,
    jobTitle: dto.job_title ?? null,
  }
}

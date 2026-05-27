export type UserStatus = 'active' | 'invited'
export type UserRole = 'admin' | 'editor'

export interface User {
  id: number
  email: string
  role: UserRole
  organizationId: number | null
  orgRole: string | null
  status: UserStatus
  displayName: string | null
  fullName: string | null
  jobTitle: string | null
  createdAt: string | null
  updatedAt: string | null
}

export interface UserList {
  items: User[]
}

export interface CreateUserInput {
  email: string
  password: string
  role: UserRole
}

export interface UpdateUserRoleInput {
  role: UserRole
}

export interface AdminResetPasswordInput {
  newPassword: string
}

export interface ChangeOwnPasswordInput {
  currentPassword: string
  newPassword: string
}

export interface InviteUserInput {
  email: string
  role: UserRole
}

export interface ChangeEmailInput {
  email: string
}

export interface UpdateUserProfileInput {
  displayName: string | null
  fullName: string | null
  jobTitle: string | null
}

export interface UserProfile {
  displayName: string | null
  fullName: string | null
  jobTitle: string | null
}

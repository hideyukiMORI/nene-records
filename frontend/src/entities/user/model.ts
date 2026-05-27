export type UserStatus = 'active' | 'invited'
export type UserRole = 'admin' | 'editor'

export interface User {
  id: number
  email: string
  role: UserRole
  status: UserStatus
  createdAt: string
  updatedAt: string
}

export interface UserList {
  users: User[]
}

export interface ChangeEmailInput {
  userId: number
  email: string
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

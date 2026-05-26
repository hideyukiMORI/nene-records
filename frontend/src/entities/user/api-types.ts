export interface UserDto {
  id: number
  email: string
  role: string
  status: string
  created_at: string
  updated_at: string
}

export interface UserListDto {
  users: UserDto[]
}

export interface CreateUserRequestDto {
  email: string
  password: string
  role: string
}

export interface UpdateUserRoleRequestDto {
  role: string
}

export interface AdminResetPasswordRequestDto {
  new_password: string
}

export interface ChangeOwnPasswordRequestDto {
  current_password: string
  new_password: string
}

export interface InviteUserRequestDto {
  email: string
  role: string
}

export interface InviteUserResponseDto {
  id: number
  email: string
  role: string
  status: string
}

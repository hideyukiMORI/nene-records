export interface UserDto {
  id: number
  email: string
  role: string
  organization_id: number | null
  org_role: string | null
  status: string
  // Profile fields — present in GET /users/{id} but absent in GET /users (list)
  display_name?: string | null
  full_name?: string | null
  job_title?: string | null
  created_at: string | null
  updated_at: string | null
}

export interface UserListDto {
  items: UserDto[]
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

export interface ChangeUserEmailRequestDto {
  email: string
}

export interface UpdateUserProfileRequestDto {
  display_name: string | null
  full_name: string | null
  job_title: string | null
}

export interface UserProfileDto {
  display_name: string | null
  full_name: string | null
  job_title: string | null
}

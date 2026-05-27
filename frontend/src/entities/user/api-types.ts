/**
 * User entity API types — auto-derived from OpenAPI schema.
 *
 * DO NOT hand-edit these types. Run `npm run codegen` to regenerate
 * after updating docs/openapi/openapi.yaml.
 */
import type { components } from '@/shared/api/schema.gen'

export type UserDto = components['schemas']['UserResponse']
export type UserListDto = components['schemas']['UserListResponse']
export type UserProfileDto = components['schemas']['UserProfileResponse']
export type CreateUserRequestDto = components['schemas']['CreateUserRequest']
export type UpdateUserRoleRequestDto = components['schemas']['UpdateUserRoleRequest']
export type AdminResetPasswordRequestDto = components['schemas']['AdminResetPasswordRequest']
export type ChangeOwnPasswordRequestDto = components['schemas']['ChangeOwnPasswordRequest']
export type InviteUserRequestDto = components['schemas']['InviteUserRequest']
export type InviteUserResponseDto = components['schemas']['UserResponse']
export type ChangeUserEmailRequestDto = components['schemas']['ChangeUserEmailRequest']
export type UpdateUserProfileRequestDto = components['schemas']['UpdateUserProfileRequest']

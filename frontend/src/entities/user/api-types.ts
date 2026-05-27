/**
 * User entity API types — auto-derived from OpenAPI schema.
 *
 * DO NOT hand-edit these types. Run `npm run codegen` to regenerate
 * after updating docs/openapi/openapi.yaml.
 */
import type { components } from '@/shared/api/schema.gen'

// Response types (used in queries/mutations for response mapping)
export type UserDto = components['schemas']['UserResponse']
export type UserListDto = components['schemas']['UserListResponse']
export type UserProfileDto = components['schemas']['UserProfileResponse']
export type InviteUserResponseDto = components['schemas']['UserResponse']

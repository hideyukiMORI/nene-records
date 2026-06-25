import type { components } from '@/shared/api/schema.gen'

export interface LoginRequestDto {
  email: string
  password: string
}

export interface LoginResponseDto {
  token: string
  expires_at: string
  email: string
  role: string
  email_verified: boolean
}

export type SignupRequestDto = components['schemas']['SignupRequest']
export type SignupResponseDto = components['schemas']['SignupResponse']

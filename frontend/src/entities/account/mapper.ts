import type { AccountResponseDto } from './api-types'
import type { Account } from './model'

export function mapAccountResponseDtoToModel(dto: AccountResponseDto): Account {
  return {
    slug: dto.slug,
    name: dto.name,
    plan: dto.plan,
    customDomain: dto.custom_domain,
    entitlements: {
      customDomainAllowed: dto.entitlements.custom_domain_allowed,
      maxRecords: dto.entitlements.max_records,
      maxStorageBytes: dto.entitlements.max_storage_bytes,
      maxAdminUsers: dto.entitlements.max_admin_users,
    },
    recordsUsed: dto.usage.records,
  }
}

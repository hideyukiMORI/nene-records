import type {
  OrganizationDto,
  OrganizationListDto,
  CreateOrganizationDto,
  UpdateOrganizationDto,
} from './api-types'
import type {
  Organization,
  OrganizationList,
  CreateOrganizationInput,
  UpdateOrganizationInput,
} from './model'

export function mapOrganizationDtoToModel(dto: OrganizationDto): Organization {
  return {
    id: dto.id,
    name: dto.name,
    slug: dto.slug,
    customDomain: dto.custom_domain,
    plan: dto.plan,
    isActive: dto.is_active,
    createdAt: dto.created_at,
    updatedAt: dto.updated_at,
  }
}

export function mapOrganizationListDtoToModel(dto: OrganizationListDto): OrganizationList {
  return {
    items: dto.data.map(mapOrganizationDtoToModel),
    total: dto.meta.total,
    limit: dto.meta.limit,
    offset: dto.meta.offset,
  }
}

export function mapCreateInputToDto(input: CreateOrganizationInput): CreateOrganizationDto {
  return {
    name: input.name,
    slug: input.slug,
    plan: input.plan,
    custom_domain: input.customDomain ?? null,
  }
}

export function mapUpdateInputToDto(input: UpdateOrganizationInput): UpdateOrganizationDto {
  return {
    ...(input.name !== undefined ? { name: input.name } : {}),
    ...(input.slug !== undefined ? { slug: input.slug } : {}),
    ...(input.plan !== undefined ? { plan: input.plan } : {}),
    ...(input.isActive !== undefined ? { is_active: input.isActive } : {}),
    ...(input.customDomain !== undefined ? { custom_domain: input.customDomain } : {}),
  }
}

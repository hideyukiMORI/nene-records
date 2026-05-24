import type {
  PublicSettingItemDto,
  PublicSettingListDto,
  SettingAdminItemDto,
  SettingListDto,
  SettingRevisionDto,
  SettingRevisionListDto,
  SettingValueDto,
  UpdateSettingRequestDto,
} from './api-types'
import type {
  PublicSettingItem,
  PublicSettingList,
  SettingItem,
  SettingList,
  SettingRevision,
  SettingRevisionList,
  UpdateSettingInput,
} from './model'

export function mapSettingAdminItemDtoToModel(dto: SettingAdminItemDto): SettingItem {
  return {
    settingKey: dto.setting_key,
    label: dto.label,
    dataType: dto.data_type,
    defaultValue: dto.default_value,
    isPublic: dto.is_public,
    value: dto.value,
    updatedAt: dto.updated_at,
  }
}

export function mapSettingListDtoToModel(dto: SettingListDto): SettingList {
  return {
    items: dto.items.map(mapSettingAdminItemDtoToModel),
  }
}

export function mapPublicSettingItemDtoToModel(dto: PublicSettingItemDto): PublicSettingItem {
  return {
    settingKey: dto.setting_key,
    value: dto.value,
  }
}

export function mapPublicSettingListDtoToModel(dto: PublicSettingListDto): PublicSettingList {
  return {
    items: dto.items.map(mapPublicSettingItemDtoToModel),
  }
}

export function mapSettingRevisionDtoToModel(dto: SettingRevisionDto): SettingRevision {
  return {
    id: dto.id,
    settingKey: dto.setting_key,
    value: dto.value,
    previousValue: dto.previous_value,
    action: dto.action,
    actorUserId: dto.actor_user_id,
    createdAt: dto.created_at,
  }
}

export function mapSettingRevisionListDtoToModel(dto: SettingRevisionListDto): SettingRevisionList {
  return {
    items: dto.items.map(mapSettingRevisionDtoToModel),
    limit: dto.limit,
    offset: dto.offset,
  }
}

export function mapUpdateSettingInputToDto(input: UpdateSettingInput): UpdateSettingRequestDto {
  return { value: input.value }
}

export function mapSettingValueDtoToModel(
  dto: SettingValueDto,
): Pick<SettingItem, 'settingKey' | 'value' | 'updatedAt'> {
  return {
    settingKey: dto.setting_key,
    value: dto.value,
    updatedAt: dto.updated_at,
  }
}

export function publicSettingsToMap(items: PublicSettingItem[]): Record<string, string> {
  return Object.fromEntries(items.map((item) => [item.settingKey, item.value]))
}

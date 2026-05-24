export interface SettingAdminItemDto {
  setting_key: string
  label: string
  data_type: 'text' | 'markdown' | 'bool' | 'url'
  default_value: string | null
  is_public: boolean
  value: string
  updated_at: string | null
}

export interface SettingListDto {
  items: SettingAdminItemDto[]
}

export interface PublicSettingItemDto {
  setting_key: string
  value: string
}

export interface PublicSettingListDto {
  items: PublicSettingItemDto[]
}

export interface SettingValueDto {
  setting_key: string
  value: string
  updated_at: string
}

export interface SettingRevisionDto {
  id: number
  setting_key: string
  value: string | null
  previous_value: string | null
  action: 'created' | 'updated' | 'deleted' | 'restored'
  actor_user_id: number | null
  created_at: string
}

export interface SettingRevisionListDto {
  items: SettingRevisionDto[]
  limit: number
  offset: number
}

export interface UpdateSettingRequestDto {
  value: string
}

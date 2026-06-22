export type SettingKey = string

export type SettingDataType = 'text' | 'markdown' | 'bool' | 'url' | 'media'

export interface SettingItem {
  settingKey: SettingKey
  label: string
  dataType: SettingDataType
  defaultValue: string | null
  isPublic: boolean
  value: string
  updatedAt: string | null
}

export interface SettingList {
  items: SettingItem[]
}

export interface PublicSettingItem {
  settingKey: SettingKey
  value: string
}

export interface PublicSettingList {
  items: PublicSettingItem[]
}

export interface SettingRevision {
  id: number
  settingKey: SettingKey
  value: string | null
  previousValue: string | null
  action: 'created' | 'updated' | 'deleted' | 'restored'
  actorUserId: number | null
  createdAt: string
}

export interface SettingRevisionList {
  items: SettingRevision[]
  limit: number
  offset: number
}

export interface UpdateSettingInput {
  value: string
}

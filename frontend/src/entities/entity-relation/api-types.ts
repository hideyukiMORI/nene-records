export interface EntityRelationItemDto {
  field_key: string
  target_entity_id: number
}

export interface EntityRelationListDto {
  items: EntityRelationItemDto[]
}

export interface AttachEntityRelationDto {
  field_key: string
  target_entity_id: number
}

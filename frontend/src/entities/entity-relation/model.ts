export interface EntityRelation {
  fieldKey: string
  targetEntityId: number
}

export interface EntityRelationList {
  items: EntityRelation[]
}

export interface AttachEntityRelationInput {
  entityId: number
  fieldKey: string
  targetEntityId: number
}

export interface DetachEntityRelationInput {
  entityId: number
  fieldKey: string
  targetEntityId: number
}

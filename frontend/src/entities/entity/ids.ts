declare const entityIdBrand: unique symbol

export type EntityId = number & { readonly [entityIdBrand]: never }

export function toEntityId(value: number): EntityId {
  return value as EntityId
}

declare const entityTypeIdBrand: unique symbol

export type EntityTypeId = number & { readonly [entityTypeIdBrand]: never }

export function toEntityTypeId(value: number): EntityTypeId {
  return value as EntityTypeId
}

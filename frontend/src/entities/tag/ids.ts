declare const tagIdBrand: unique symbol

export type TagId = number & { readonly [tagIdBrand]: never }

export function toTagId(value: number): TagId {
  return value as TagId
}

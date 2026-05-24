declare const fieldDefIdBrand: unique symbol

export type FieldDefId = number & { readonly [fieldDefIdBrand]: never }

export function toFieldDefId(value: number): FieldDefId {
  return value as FieldDefId
}

declare const boolFieldIdBrand: unique symbol

export type BoolFieldId = number & { readonly [boolFieldIdBrand]: never }

export function toBoolFieldId(value: number): BoolFieldId {
  return value as BoolFieldId
}

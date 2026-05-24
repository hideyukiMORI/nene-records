declare const enumFieldIdBrand: unique symbol

export type EnumFieldId = number & { readonly [enumFieldIdBrand]: never }

export function toEnumFieldId(value: number): EnumFieldId {
  return value as EnumFieldId
}

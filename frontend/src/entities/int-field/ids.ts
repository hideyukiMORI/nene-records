declare const intFieldIdBrand: unique symbol

export type IntFieldId = number & { readonly [intFieldIdBrand]: never }

export function toIntFieldId(value: number): IntFieldId {
  return value as IntFieldId
}

declare const textFieldIdBrand: unique symbol

export type TextFieldId = number & { readonly [textFieldIdBrand]: never }

export function toTextFieldId(value: number): TextFieldId {
  return value as TextFieldId
}

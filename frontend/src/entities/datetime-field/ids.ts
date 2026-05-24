declare const dateTimeFieldIdBrand: unique symbol

export type DateTimeFieldId = number & { readonly [dateTimeFieldIdBrand]: never }

export function toDateTimeFieldId(value: number): DateTimeFieldId {
  return value as DateTimeFieldId
}

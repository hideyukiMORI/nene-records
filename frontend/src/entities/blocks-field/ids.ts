declare const blocksFieldIdBrand: unique symbol

export type BlocksFieldId = number & { readonly [blocksFieldIdBrand]: never }

export function toBlocksFieldId(value: number): BlocksFieldId {
  return value as BlocksFieldId
}

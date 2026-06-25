import type { TextField } from '@/entities/text-field'

/**
 * Resolve a record's display label from its text fields: the `title` field if
 * present, else the first non-empty field, else the fallback.
 *
 * When `locale` is given (public i18n, #540) each step prefers that locale, then
 * the locale-agnostic (null) row, then the first — so listings show titles in the
 * visitor's language and fall back gracefully. With no `locale` the behavior is
 * unchanged (first match), so admin callers are unaffected.
 */
export function getRecordDisplayLabel(
  entityId: number,
  textFields: TextField[],
  fallback: string,
  locale: string | null = null,
): string {
  const forEntity = textFields.filter(
    (item) => item.entityId === entityId && item.value.trim() !== '',
  )

  const pick = (candidates: TextField[]): TextField | undefined => {
    if (candidates.length === 0) {
      return undefined
    }
    if (locale !== null) {
      return (
        candidates.find((c) => c.locale === locale) ??
        candidates.find((c) => c.locale === null) ??
        candidates[0]
      )
    }
    return candidates[0]
  }

  const title = pick(forEntity.filter((item) => item.fieldKey === 'title'))
  if (title !== undefined) {
    return title.value.trim()
  }

  const first = pick(forEntity)
  if (first !== undefined) {
    return first.value.trim()
  }

  return fallback
}

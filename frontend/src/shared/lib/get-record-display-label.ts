import type { TextField } from '@/entities/text-field'

/**
 * Resolve a record's display label: the `title` field if present, else the
 * entity's SEO `metaTitle` when the caller provides one (#853), else the first
 * non-empty field, else the fallback.
 *
 * When `locale` is given (public i18n, #540) each step prefers that locale, then
 * the locale-agnostic (null) row, then the first — so listings show titles in the
 * visitor's language and fall back gracefully. With no `locale` the behavior is
 * unchanged (first match), so admin callers are unaffected.
 *
 * The non-title fallback is a *derived* label, so it is normalized for display:
 * markup is stripped and the text capped (#849). A bespoke page whose only field
 * is a full html document (bare layout, #799) must not dump its source into
 * listings. An explicit `title` field is trusted as-is.
 */
const FALLBACK_LABEL_MAX = 120

/** Strip markup, collapse whitespace, and cap for use as a derived label. */
export function toDerivedLabel(value: string): string {
  const text = value
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
  if (text.length <= FALLBACK_LABEL_MAX) {
    return text
  }
  return text.slice(0, FALLBACK_LABEL_MAX).trimEnd() + '…'
}

export function getRecordDisplayLabel(
  entityId: number,
  textFields: TextField[],
  fallback: string,
  locale: string | null = null,
  metaTitle: string | null = null,
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

  // SEO meta_title beats the derived excerpt: bespoke pages sharing one html
  // field would otherwise all show the same stripped header/nav text (#853).
  if (metaTitle !== null && metaTitle.trim() !== '') {
    return metaTitle.trim()
  }

  const first = pick(forEntity)
  if (first !== undefined) {
    const derived = toDerivedLabel(first.value)
    if (derived !== '') {
      return derived
    }
  }

  return fallback
}

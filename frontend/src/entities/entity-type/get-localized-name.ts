import type { EntityType } from './model'

/**
 * Returns the display name for an entity type in the given locale.
 * Falls back to the base `name` if no label is set for that locale.
 *
 * @example
 * getLocalizedEntityTypeName({ name: 'Posts', labels: { ja: '投稿' } }, 'ja') // '投稿'
 * getLocalizedEntityTypeName({ name: 'Posts', labels: { ja: '投稿' } }, 'en') // 'Posts'
 */
export function getLocalizedEntityTypeName(
  entityType: Pick<EntityType, 'name' | 'labels'>,
  locale: string,
): string {
  if (entityType.labels === undefined) return entityType.name
  return entityType.labels[locale] ?? entityType.name
}

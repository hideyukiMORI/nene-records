import { Link } from 'react-router-dom'
import type { Entity } from '@/entities/entity'
import type { EntityType } from '@/entities/entity-type'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { useTranslation } from '@/shared/i18n'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'
import { resolvePermalink } from '@/shared/lib/resolve-permalink'

export interface PublicEntityResultGroupProps {
  entityType: EntityType
  entities: Entity[]
}

/** One entity type's results, with titles resolved from its text fields. */
export function PublicEntityResultGroup({ entityType, entities }: PublicEntityResultGroupProps) {
  const { t, locale } = useTranslation()
  const entityTypeId = Number(entityType.id)
  const textFieldQuery = useTextFieldList(defaultTextFieldListParamsForEntityType(entityTypeId), {
    enabled: entityTypeId > 0,
  })
  const textFields = textFieldQuery.data?.items ?? []

  return (
    <section className="resultgroup">
      <h2 className="resultgroup__title">{entityType.name}</h2>
      <div className="rowlist">
        {entities.map((entity) => {
          const id = Number(entity.id)
          const url = resolvePermalink(entityType.permalinkPattern, {
            typeSlug: entityType.slug,
            entitySlug: entity.slug ?? null,
            entityId: id,
            publishedAt: entity.publishedAt ?? null,
          })
          return (
            <article key={id} className="row row--compact">
              <div className="row__body">
                <div className="row__metarow">
                  <Link className="tbadge" to={`/${entityType.slug}`}>
                    {entityType.name.toLowerCase()}
                  </Link>
                </div>
                <h3 className="row__title">
                  <Link to={url}>
                    {getRecordDisplayLabel(
                      id,
                      textFields,
                      t('public.results.recordFallback', { id }),
                      locale,
                    )}
                  </Link>
                </h3>
              </div>
            </article>
          )
        })}
      </div>
    </section>
  )
}

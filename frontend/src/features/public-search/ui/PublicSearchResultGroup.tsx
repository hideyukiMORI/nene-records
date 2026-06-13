import { Link } from 'react-router-dom'
import type { Entity } from '@/entities/entity'
import type { EntityType } from '@/entities/entity-type'
import { defaultTextFieldListParamsForEntityType, useTextFieldList } from '@/entities/text-field'
import { getRecordDisplayLabel } from '@/shared/lib/get-record-display-label'
import { resolvePermalink } from '@/shared/lib/resolve-permalink'
import { Stack, Text } from '@/shared/ui'

export interface PublicSearchResultGroupProps {
  entityType: EntityType
  entities: Entity[]
}

/** One entity type's search hits, with titles resolved from its text fields. */
export function PublicSearchResultGroup({ entityType, entities }: PublicSearchResultGroupProps) {
  const entityTypeId = Number(entityType.id)
  const textFieldQuery = useTextFieldList(defaultTextFieldListParamsForEntityType(entityTypeId), {
    enabled: entityTypeId > 0,
  })
  const textFields = textFieldQuery.data?.items ?? []

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        {entityType.name}
      </Text>
      <ul className="flex flex-col gap-stack-xs">
        {entities.map((entity) => {
          const id = Number(entity.id)
          const url = resolvePermalink(entityType.permalinkPattern, {
            typeSlug: entityType.slug,
            entitySlug: entity.slug ?? null,
            entityId: id,
            publishedAt: entity.publishedAt ?? null,
          })
          return (
            <li key={id}>
              <Link to={url} className="text-body text-accent underline hover:no-underline">
                {getRecordDisplayLabel(id, textFields, `Record #${String(id)}`)}
              </Link>
            </li>
          )
        })}
      </ul>
    </Stack>
  )
}

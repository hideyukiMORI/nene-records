import { useMemo } from 'react'
import { Link } from 'react-router-dom'
import { useEntityTypeList } from '@/entities/entity-type'
import { usePopularEntities } from '@/entities/popular-entity'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { resolvePermalink } from '@/shared/lib/resolve-permalink'
import { formatPostDate } from '@/shared/lib/widget-post-meta'
import { Text } from '@/shared/ui'

export interface PopularPostsWidgetProps {
  widget: Widget
}

/** Lists the most-viewed published records as links. Nothing when there are none. */
export function PopularPostsWidget({ widget }: PopularPostsWidgetProps) {
  const { t, locale } = useTranslation()
  const rawLimit = widget.settings['limit']
  const limit = typeof rawLimit === 'number' && rawLimit > 0 ? rawLimit : 5
  const showDate = widget.settings['showDate'] === true

  const { data } = usePopularEntities({ limit })
  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })

  const typeById = useMemo(
    () => new Map((entityTypeQuery.data?.items ?? []).map((type) => [Number(type.id), type])),
    [entityTypeQuery.data?.items],
  )

  const items = data?.items ?? []

  if (items.length === 0) {
    return (
      <Text muted variant="caption">
        {t('widgets.popularPosts.empty')}
      </Text>
    )
  }

  return (
    <ul className="flex flex-col gap-stack-xs">
      {items.map((item) => {
        const type = typeById.get(item.entityTypeId)
        const url = resolvePermalink(type?.permalinkPattern, {
          typeSlug: type?.slug ?? '',
          entitySlug: item.slug,
          entityId: item.entityId,
          publishedAt: item.publishedAt,
        })
        const date = showDate ? formatPostDate(item.publishedAt, locale) : ''
        return (
          <li key={item.entityId} className="flex flex-col gap-stack-xs">
            <Link to={url} className="text-body text-accent underline hover:no-underline">
              {item.title ?? `Record #${String(item.entityId)}`}
            </Link>
            {date !== '' ? (
              <Text as="span" muted variant="caption">
                {date}
              </Text>
            ) : null}
          </li>
        )
      })}
    </ul>
  )
}

import { Link } from 'react-router-dom'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { formatPostDate } from '@/shared/lib/widget-post-meta'
import { Text } from '@/shared/ui'
import { useRecentPosts } from '../hooks/use-recent-posts'

export interface RecentPostsWidgetProps {
  widget: Widget
}

/** Lists recent published records of a configured entity type as links. */
export function RecentPostsWidget({ widget }: RecentPostsWidgetProps) {
  const { t, locale } = useTranslation()
  const entityTypeSlug =
    typeof widget.settings['entityTypeSlug'] === 'string' ? widget.settings['entityTypeSlug'] : ''
  const rawLimit = widget.settings['limit']
  const limit = typeof rawLimit === 'number' && rawLimit > 0 ? rawLimit : 5
  const showDate = widget.settings['showDate'] === true
  const showExcerpt = widget.settings['showExcerpt'] === true

  const { items } = useRecentPosts(entityTypeSlug, limit)

  if (entityTypeSlug === '') {
    return (
      <Text muted variant="caption">
        {t('widgets.recentPosts.unconfigured')}
      </Text>
    )
  }

  if (items.length === 0) {
    return (
      <Text muted variant="caption">
        {t('widgets.recentPosts.empty')}
      </Text>
    )
  }

  return (
    <ul className="flex flex-col gap-stack-sm">
      {items.map((item) => {
        const date = showDate ? formatPostDate(item.publishedAt, locale) : ''
        const excerpt = showExcerpt ? item.excerpt : ''
        return (
          <li key={item.id} className="flex flex-col gap-stack-xs">
            <Link
              to={item.publicUrl}
              className="text-body text-accent underline hover:no-underline"
            >
              {item.label}
            </Link>
            {date !== '' ? (
              <Text as="span" muted variant="caption">
                {date}
              </Text>
            ) : null}
            {excerpt !== '' ? (
              <Text as="span" muted variant="caption">
                {excerpt}
              </Text>
            ) : null}
          </li>
        )
      })}
    </ul>
  )
}

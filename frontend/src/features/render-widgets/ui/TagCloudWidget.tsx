import { Link } from 'react-router-dom'
import { useTagList } from '@/entities/tag'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'

/** Lists all tags as links to their archive pages. Renders nothing when empty. */
export function TagCloudWidget() {
  const { t } = useTranslation()
  const { data } = useTagList({ limit: 100, offset: 0 })
  const tags = data?.items ?? []

  if (tags.length === 0) {
    return (
      <Text muted variant="caption">
        {t('widgets.tagCloud.empty')}
      </Text>
    )
  }

  return (
    <ul className="flex flex-wrap gap-inline-sm">
      {tags.map((tag) => (
        <li key={String(tag.id)}>
          <Link
            to={`/tag/${encodeURIComponent(tag.slug)}`}
            className="text-body text-accent underline hover:no-underline"
          >
            {tag.name}
          </Link>
        </li>
      ))}
    </ul>
  )
}

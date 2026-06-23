import { Link } from 'react-router-dom'
import type { MediaUsage } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'

export interface MediaUsageListProps {
  usages: MediaUsage[]
  isLoading: boolean
}

/**
 * MediaUsageList — renders where a media item is referenced, inside the delete
 * dialog. While any usage exists the parent disables the confirm button.
 *
 * Does not: fetch data or perform mutations.
 */
export function MediaUsageList({ usages, isLoading }: MediaUsageListProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.media.usages.loading')}</Text>
  }

  if (usages.length === 0) {
    return null
  }

  return (
    <div
      role="alert"
      className="rounded-md border border-warning bg-warning-weak px-inline-sm py-stack-xs"
    >
      <Stack gap="xs">
        <Text variant="caption" className="font-medium text-warning">
          {t('admin.media.usages.blocked', { count: usages.length })}
        </Text>
        <ul className="flex flex-col gap-stack-xs">
          {usages.map((usage) => (
            <li
              key={`${String(usage.entityId)}-${usage.fieldKey}`}
              className="text-caption leading-body text-warning"
            >
              <Link
                to={`/admin/${usage.entityTypeSlug}/${String(usage.entityId)}`}
                className="underline hover:no-underline"
              >
                {usage.title ?? usage.entitySlug}
              </Link>{' '}
              <span className="opacity-70">
                ({usage.entityTypeSlug} · {usage.fieldKey})
              </span>
            </li>
          ))}
        </ul>
      </Stack>
    </div>
  )
}

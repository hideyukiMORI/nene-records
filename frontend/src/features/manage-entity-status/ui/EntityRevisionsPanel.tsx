import { useState } from 'react'
import { toEntityId, useEntityRevisions } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

function RevisionsList({ entityId }: { entityId: number }) {
  const { t } = useTranslation()
  const revisionsQuery = useEntityRevisions(toEntityId(entityId))

  if (revisionsQuery.isLoading) {
    return <Text muted>{t('admin.entityRevisions.loading')}</Text>
  }

  if (revisionsQuery.isError) {
    return <Text muted>{t('admin.entityRevisions.error')}</Text>
  }

  const items = revisionsQuery.data?.items ?? []

  if (items.length === 0) {
    return <Text muted>{t('admin.entityRevisions.empty')}</Text>
  }

  return (
    <Stack gap="xs">
      {items.map((revision) => (
        <Text key={revision.id} muted variant="caption">
          {revision.createdAt} · {revision.action} · {revision.status}
          {revision.previousStatus !== null && revision.previousStatus !== revision.status
            ? ` ← ${revision.previousStatus}`
            : ''}
          {revision.slug !== null ? ` · /${revision.slug}` : ''}
        </Text>
      ))}
    </Stack>
  )
}

export function EntityRevisionsPanel({ entityId }: { entityId: number }) {
  const { t } = useTranslation()
  const [isExpanded, setIsExpanded] = useState(false)

  return (
    <section className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm">
      <Stack gap="md">
        <div className="flex items-center justify-between">
          <Text as="h2" variant="heading-sm">
            {t('admin.entityRevisions.title')}
          </Text>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              setIsExpanded((prev) => !prev)
            }}
          >
            {isExpanded ? t('admin.entityRevisions.hide') : t('admin.entityRevisions.show')}
          </Button>
        </div>
        {isExpanded ? <RevisionsList entityId={entityId} /> : null}
      </Stack>
    </section>
  )
}

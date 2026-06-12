import type { EntityRevision } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Stack, Text } from '@/shared/ui'

interface EntityRevisionsPanelProps {
  revisions: EntityRevision[]
  isLoading: boolean
  isError: boolean
  isExpanded: boolean
  onToggle: () => void
}

export function EntityRevisionsPanel({
  revisions,
  isLoading,
  isError,
  isExpanded,
  onToggle,
}: EntityRevisionsPanelProps) {
  const { t } = useTranslation()

  return (
    <Card as="section">
      <Stack gap="md">
        <div className="flex items-center justify-between">
          <Text as="h2" variant="heading-sm">
            {t('admin.entityRevisions.title')}
          </Text>
          <Button variant="secondary" size="sm" onClick={onToggle}>
            {isExpanded ? t('admin.entityRevisions.hide') : t('admin.entityRevisions.show')}
          </Button>
        </div>

        {isExpanded ? (
          isLoading ? (
            <Text muted>{t('admin.entityRevisions.loading')}</Text>
          ) : isError ? (
            <Text muted>{t('admin.entityRevisions.error')}</Text>
          ) : revisions.length === 0 ? (
            <Text muted>{t('admin.entityRevisions.empty')}</Text>
          ) : (
            <Stack gap="xs">
              {revisions.map((revision) => (
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
        ) : null}
      </Stack>
    </Card>
  )
}

import type { Entity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'
import { LoadingArticle } from '@/shared/ui/loading'
import type { PublicFieldRow } from '../hooks/use-public-view-entity-record-page'
import { PublicRecordFieldList } from './PublicRecordFieldList'

export interface PublicRecordDetailViewProps {
  entity: Entity | null
  fieldRows: PublicFieldRow[]
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicRecordDetailView({
  entity,
  fieldRows,
  entityTypeSlugById,
  entityTypePatternById,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicRecordDetailViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    // Skeleton, not text (#894/#905): the layout is known here (B群), so mirror the
    // article body's shape — the swap must not move the page, and the signal must
    // stay language-independent like the rest of the loading states.
    return <LoadingArticle />
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">{t('public.record.error.title')}</Text>
        <Text muted>{errorTitle ?? t('common.error.unknown')}</Text>
        <Button variant="secondary" onClick={onRetry}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
    )
  }

  if (entity === null) {
    return <Text muted>{t('public.record.notFound.title')}</Text>
  }

  if (fieldRows.length === 0) {
    return <Text muted>{t('public.record.noFields')}</Text>
  }

  return (
    <PublicRecordFieldList
      entity={entity}
      fieldRows={fieldRows}
      entityTypeSlugById={entityTypeSlugById}
      entityTypePatternById={entityTypePatternById}
    />
  )
}

import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { useTranslation } from '@/shared/i18n'
import { EmptyState, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'

export interface PublicTagArchiveViewProps {
  tagName: string
  groups: PublicEntityTypeGroup[]
  total: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicTagArchiveView({
  tagName,
  groups,
  total,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicTagArchiveViewProps) {
  const { t } = useTranslation()

  return (
    <Stack gap="lg">
      <Text as="h1" variant="heading-md">
        {t('public.tagArchive.title', { tag: tagName })}
      </Text>

      {isLoading ? (
        <LoadingState>{t('public.tagArchive.loading')}</LoadingState>
      ) : isError ? (
        <ErrorState
          message={errorTitle ?? t('common.error.unknown')}
          onRetry={onRetry}
          retryLabel={t('common.actions.retry')}
        />
      ) : total === 0 ? (
        <EmptyState
          title={t('public.tagArchive.empty.title')}
          description={t('public.tagArchive.empty.description', { tag: tagName })}
        />
      ) : (
        <Stack gap="lg">
          <Text muted variant="caption">
            {t('public.tagArchive.resultCount', { count: String(total) })}
          </Text>
          {groups.map((group) => (
            <PublicEntityResultGroup
              key={String(group.entityType.id)}
              entityType={group.entityType}
              entities={group.entities}
            />
          ))}
        </Stack>
      )}
    </Stack>
  )
}

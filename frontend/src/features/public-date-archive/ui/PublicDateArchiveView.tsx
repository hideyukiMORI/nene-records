import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { useTranslation } from '@/shared/i18n'
import { EmptyState, ErrorState, LoadingState, Stack, Text } from '@/shared/ui'

export interface PublicDateArchiveViewProps {
  title: string
  valid: boolean
  groups: PublicEntityTypeGroup[]
  total: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

export function PublicDateArchiveView({
  title,
  valid,
  groups,
  total,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: PublicDateArchiveViewProps) {
  const { t } = useTranslation()

  return (
    <Stack gap="lg">
      <Text as="h1" variant="heading-md">
        {title}
      </Text>

      {!valid ? (
        <EmptyState
          title={t('public.dateArchive.invalid.title')}
          description={t('public.dateArchive.invalid.description')}
        />
      ) : isLoading ? (
        <LoadingState>{t('public.dateArchive.loading')}</LoadingState>
      ) : isError ? (
        <ErrorState
          message={errorTitle ?? t('common.error.unknown')}
          onRetry={onRetry}
          retryLabel={t('common.actions.retry')}
        />
      ) : total === 0 ? (
        <EmptyState
          title={t('public.dateArchive.empty.title')}
          description={t('public.dateArchive.empty.description')}
        />
      ) : (
        <Stack gap="lg">
          <Text muted variant="caption">
            {t('public.dateArchive.resultCount', { count: String(total) })}
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

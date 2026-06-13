import { useState } from 'react'
import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, ErrorState, Input, LoadingState, Stack, Text } from '@/shared/ui'

export interface PublicSearchViewProps {
  query: string
  hasQuery: boolean
  groups: PublicEntityTypeGroup[]
  total: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onSearch: (q: string) => void
  onRetry: () => void
}

export function PublicSearchView({
  query,
  hasQuery,
  groups,
  total,
  isLoading,
  isError,
  errorTitle,
  onSearch,
  onRetry,
}: PublicSearchViewProps) {
  const { t } = useTranslation()
  const [input, setInput] = useState(query)

  return (
    <Stack gap="lg">
      <Text as="h1" variant="heading-md">
        {t('public.search.title')}
      </Text>

      <form
        className="flex items-end gap-inline-sm"
        onSubmit={(event) => {
          event.preventDefault()
          onSearch(input.trim())
        }}
      >
        <div className="flex-1">
          <Input
            id="public-search-input"
            label={t('public.search.label')}
            placeholder={t('public.search.placeholder')}
            value={input}
            autoComplete="off"
            onChange={(event) => {
              setInput(event.target.value)
            }}
          />
        </div>
        <Button type="submit">{t('public.search.submit')}</Button>
      </form>

      {!hasQuery ? (
        <Text muted>{t('public.search.prompt')}</Text>
      ) : isLoading ? (
        <LoadingState>{t('public.search.loading')}</LoadingState>
      ) : isError ? (
        <ErrorState
          message={errorTitle ?? t('common.error.unknown')}
          onRetry={onRetry}
          retryLabel={t('common.actions.retry')}
        />
      ) : total === 0 ? (
        <EmptyState
          title={t('public.search.empty.title')}
          description={t('public.search.empty.description', { query })}
        />
      ) : (
        <Stack gap="lg">
          <Text muted variant="caption">
            {t('public.search.resultCount', { count: String(total) })}
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

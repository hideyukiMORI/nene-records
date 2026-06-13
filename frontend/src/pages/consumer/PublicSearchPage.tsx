import { useSearchParams } from 'react-router-dom'
import { PublicSearchView, usePublicSearchPage } from '@/features/public-search'
import { PublicLayout } from './PublicLayout'
import { usePublicSite } from './public-site-context'

export function PublicSearchPage() {
  const site = usePublicSite()
  const [searchParams, setSearchParams] = useSearchParams()
  const q = searchParams.get('q') ?? ''

  const { query, hasQuery, groups, total, isLoading, isError, errorTitle, refetch } =
    usePublicSearchPage(q)

  return (
    <PublicLayout variant="standard" site={site}>
      <PublicSearchView
        query={query}
        hasQuery={hasQuery}
        groups={groups}
        total={total}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        onSearch={(next) => {
          setSearchParams(next === '' ? {} : { q: next })
        }}
        onRetry={() => {
          void refetch()
        }}
      />
    </PublicLayout>
  )
}

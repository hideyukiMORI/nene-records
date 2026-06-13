import { useParams } from 'react-router-dom'
import { PublicTagArchiveView, usePublicTagArchivePage } from '@/features/public-tag-archive'
import { PublicLayout } from './PublicLayout'
import { usePublicSite } from './public-site-context'

export function PublicTagArchivePage() {
  const site = usePublicSite()
  const { tagSlug = '' } = useParams()

  const { tagName, groups, total, isLoading, isError, errorTitle, refetch } =
    usePublicTagArchivePage(tagSlug)

  return (
    <PublicLayout variant="standard" site={site}>
      <PublicTagArchiveView
        tagName={tagName}
        groups={groups}
        total={total}
        isLoading={isLoading}
        isError={isError}
        errorTitle={errorTitle}
        onRetry={() => {
          void refetch()
        }}
      />
    </PublicLayout>
  )
}

import { useParams } from 'react-router-dom'
import { PublicTagArchiveView, usePublicTagArchivePage } from '@/features/public-tag-archive'
import { PublicSiteShell } from './PublicSiteShell'
import { usePublicSite } from './public-site-context'

export function PublicTagArchivePage() {
  const site = usePublicSite()
  const { tagSlug = '' } = useParams()

  const { tagName, groups, total, isLoading, isError, errorTitle, refetch } =
    usePublicTagArchivePage(tagSlug)

  return (
    <PublicSiteShell site={site}>
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
    </PublicSiteShell>
  )
}

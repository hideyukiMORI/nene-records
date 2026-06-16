import { Link } from 'react-router-dom'
import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { IconArrowLeft, IconInbox } from '@/shared/ui/icons/magazine-icons'

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
  return (
    <div className="pagehead">
      <Link className="backlink" to="/">
        <IconArrowLeft size={16} /> All records
      </Link>
      <h1 className="pagehead__title">{title}</h1>
      {valid && !isLoading && !isError && total > 0 ? (
        <p className="pagehead__sub">
          {total} record{total === 1 ? '' : 's'} published in this period.
        </p>
      ) : null}

      {!valid ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <span className="empty__icon">
            <IconInbox size={26} />
          </span>
          <h3 className="empty__title">Invalid date</h3>
          <p className="empty__text">This archive URL is not a valid date.</p>
          <Link className="btn btn--ghost" to="/">
            Back to latest
          </Link>
        </div>
      ) : isLoading ? (
        <p className="searchhint">Loading…</p>
      ) : isError ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <h3 className="empty__title">Could not load this archive</h3>
          <p className="empty__text">{errorTitle ?? 'Unknown error'}</p>
          <button type="button" className="btn btn--ghost" onClick={onRetry}>
            Retry
          </button>
        </div>
      ) : total === 0 ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <span className="empty__icon">
            <IconInbox size={26} />
          </span>
          <h3 className="empty__title">No records in this period</h3>
          <p className="empty__text">
            Nothing was published then. Pick another date, or browse the latest.
          </p>
          <Link className="btn btn--ghost" to="/">
            Back to latest
          </Link>
        </div>
      ) : (
        <div style={{ marginTop: 'var(--space-xl)' }}>
          {groups.map((group) => (
            <PublicEntityResultGroup
              key={String(group.entityType.id)}
              entityType={group.entityType}
              entities={group.entities}
            />
          ))}
        </div>
      )}
    </div>
  )
}

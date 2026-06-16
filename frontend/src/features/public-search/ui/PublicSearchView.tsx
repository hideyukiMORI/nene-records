import { useState } from 'react'
import { Link } from 'react-router-dom'
import {
  PublicEntityResultGroup,
  type PublicEntityTypeGroup,
} from '@/features/public-entity-results'
import { IconArrowLeft, IconInbox } from '@/shared/ui/icons/magazine-icons'
import { IconSearch } from '@/shared/ui/icons/Icons'

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
  const [input, setInput] = useState(query)

  return (
    <div className="pagehead">
      <Link className="backlink" to="/">
        <IconArrowLeft size={16} /> All records
      </Link>
      <h1 className="pagehead__title">Search</h1>
      <p className="pagehead__sub">Find published records by title, excerpt, or tag.</p>

      <form
        className="searchbar"
        role="search"
        onSubmit={(event) => {
          event.preventDefault()
          onSearch(input.trim())
        }}
      >
        <IconSearch size={20} />
        <input
          // eslint-disable-next-line jsx-a11y/no-autofocus
          autoFocus
          type="search"
          value={input}
          autoComplete="off"
          placeholder="Search records…"
          aria-label="Search records"
          onChange={(event) => {
            setInput(event.target.value)
          }}
        />
      </form>

      {!hasQuery ? (
        <p className="searchhint">Type a keyword and press Enter to search published records.</p>
      ) : isLoading ? (
        <p className="searchhint">Searching…</p>
      ) : isError ? (
        <div className="empty" style={{ marginTop: '2rem' }}>
          <h3 className="empty__title">Could not search</h3>
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
          <h3 className="empty__title">No results for “{query}”</h3>
          <p className="empty__text">
            No published records match that search. Try another word, or browse by type.
          </p>
          <Link className="btn btn--ghost" to="/">
            Back to latest
          </Link>
        </div>
      ) : (
        <>
          <p className="searchhint">
            {total} result{total === 1 ? '' : 's'} for “{query}”
          </p>
          <div style={{ marginTop: 'var(--space-lg)' }}>
            {groups.map((group) => (
              <PublicEntityResultGroup
                key={String(group.entityType.id)}
                entityType={group.entityType}
                entities={group.entities}
              />
            ))}
          </div>
        </>
      )}
    </div>
  )
}

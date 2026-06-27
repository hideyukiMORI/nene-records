import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { EmptyState, ErrorState, LoadingState, StatusBadge } from '@/shared/ui'
import {
  buildPermalinkTree,
  type DirectoryNode,
  type DirectoryRecord,
} from '../lib/build-permalink-tree'

/** Compact locale-aware date for tree rows (mirrors EntityListPanel). */
function formatDate(iso: string | null, locale: string): string {
  if (iso === null || iso === '') {
    return ''
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return new Intl.DateTimeFormat(locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(date)
}

export interface EntityDirectoryPanelProps {
  entityTypeSlug: string
  records: DirectoryRecord[]
  /** True when the source query hit its row cap and the tree may be incomplete. */
  truncated: boolean
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  onRetry: () => void
}

/**
 * Renders records as a collapsible directory tree derived from their permalink
 * paths (#651 PR3). Only records carrying a custom permalink appear; flat records
 * stay in the list view. parent_id is never used — the tree is path-derived.
 */
export function EntityDirectoryPanel({
  entityTypeSlug,
  records,
  truncated,
  isLoading,
  isError,
  errorTitle,
  onRetry,
}: EntityDirectoryPanelProps) {
  const { t } = useTranslation()
  const tree = useMemo(() => buildPermalinkTree(records), [records])

  if (isLoading) {
    return <LoadingState>{t('admin.entityRecords.list.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        title={t('admin.entityRecords.list.error')}
        message={errorTitle ?? t('common.error.unknown')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  if (records.length === 0) {
    return (
      <EmptyState
        title={t('admin.entityRecords.directory.empty.title')}
        description={t('admin.entityRecords.directory.empty.description')}
      />
    )
  }

  return (
    <div>
      {truncated ? (
        <p className="mb-stack-sm font-sans text-caption text-text-muted">
          {t('admin.entityRecords.directory.truncated')}
        </p>
      ) : null}
      <ul
        className="flex flex-col gap-stack-xs"
        aria-label={t('admin.entityRecords.view.directory')}
      >
        {tree.map((node) => (
          <DirectoryNodeRow key={node.path} node={node} entityTypeSlug={entityTypeSlug} depth={0} />
        ))}
      </ul>
    </div>
  )
}

function DirectoryNodeRow({
  node,
  entityTypeSlug,
  depth,
}: {
  node: DirectoryNode
  entityTypeSlug: string
  depth: number
}) {
  const { t, locale } = useTranslation()
  // Initially expand only the top level — a deep tree is otherwise a wall of rows (#657).
  const [open, setOpen] = useState(depth === 0)
  const hasChildren = node.children.length > 0

  return (
    <li>
      <div
        className="flex items-center gap-inline-sm rounded-md py-stack-xs pr-inline-sm hover:bg-surface-raised"
        style={{ paddingLeft: depth * 20 + 8 }}
      >
        {hasChildren ? (
          <button
            type="button"
            onClick={() => {
              setOpen((value) => !value)
            }}
            aria-expanded={open}
            aria-label={
              open
                ? t('admin.entityRecords.directory.collapse')
                : t('admin.entityRecords.directory.expand')
            }
            className="shrink-0 font-mono text-caption text-text-muted"
          >
            {open ? '▾' : '▸'}
          </button>
        ) : (
          <span aria-hidden="true" className="shrink-0 font-mono text-caption text-text-muted">
            ·
          </span>
        )}

        {node.record !== null ? (
          <>
            <Link
              to={`/admin/${entityTypeSlug}/${String(node.record.id)}`}
              className="truncate font-sans text-body text-text-primary hover:text-accent"
            >
              {node.record.label}
            </Link>
            {hasChildren ? (
              <span className="shrink-0 font-mono text-caption text-text-muted">
                ({node.children.length})
              </span>
            ) : null}
            <StatusBadge status={node.record.status}>
              {t(`admin.entityStatus.status.${node.record.status}`)}
            </StatusBadge>
          </>
        ) : (
          <>
            <span className="font-sans text-body font-medium text-text-muted">{node.segment}/</span>
            <span className="shrink-0 font-mono text-caption text-text-muted">
              ({node.children.length})
            </span>
          </>
        )}

        <div className="ml-auto flex shrink-0 items-center gap-inline-sm">
          {node.record !== null && formatDate(node.record.updatedAt, locale) !== '' ? (
            <span className="font-sans text-caption text-text-muted">
              {t('admin.entityRecords.list.item.updatedAt', {
                date: formatDate(node.record.updatedAt, locale),
              })}
            </span>
          ) : null}
          <code className="truncate font-mono text-caption text-text-muted">{node.path}</code>
        </div>
      </div>

      {hasChildren && open ? (
        <ul className="flex flex-col gap-stack-xs">
          {node.children.map((child) => (
            <DirectoryNodeRow
              key={child.path}
              node={child}
              entityTypeSlug={entityTypeSlug}
              depth={depth + 1}
            />
          ))}
        </ul>
      ) : null}
    </li>
  )
}

import { type DragEvent, type ReactNode, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useMoveEntity, useReorderEntities } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import {
  ConfirmDialog,
  EmptyState,
  ErrorState,
  LoadingState,
  StatusBadge,
  useToast,
} from '@/shared/ui'
import {
  buildPermalinkTree,
  type DirectoryNode,
  type DirectoryRecord,
  filterDirectoryTree,
} from '../lib/build-permalink-tree'
import {
  canDropInto,
  clearDirectoryDragPayload,
  type DirectoryDragPayload,
  getDirectoryDragPayload,
  moveInOrder,
  moveTargetPermalink,
  setDirectoryDragPayload,
} from './directory-dnd'

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

/** Wraps the first case-insensitive match of `query` in `text` for emphasis (#659). */
function highlightMatch(text: string, query: string): ReactNode {
  const needle = query.trim()
  if (needle === '') {
    return text
  }
  const index = text.toLowerCase().indexOf(needle.toLowerCase())
  if (index === -1) {
    return text
  }
  return (
    <>
      {text.slice(0, index)}
      <mark className="bg-transparent font-semibold text-accent">
        {text.slice(index, index + needle.length)}
      </mark>
      {text.slice(index + needle.length)}
    </>
  )
}

interface PendingMove {
  id: number
  label: string
  newPermalink: string
  affectedCount: number
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
  /** Create a new record under a folder, pre-filling its permalink prefix (#658). */
  onCreateHere: (permalinkPrefix: string) => void
}

/**
 * Renders records as a collapsible directory tree derived from their permalink
 * paths (#651 PR3). Only records carrying a custom permalink appear; flat records
 * stay in the list view. parent_id is never used — the tree is path-derived.
 * Records can be dragged onto a folder to move their whole subtree (#659): the
 * backend cascades descendant paths and writes 301s, gated by a confirm dialog.
 */
export function EntityDirectoryPanel({
  entityTypeSlug,
  records,
  truncated,
  isLoading,
  isError,
  errorTitle,
  onRetry,
  onCreateHere,
}: EntityDirectoryPanelProps) {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const moveMutation = useMoveEntity()
  const reorderMutation = useReorderEntities()
  const [pendingMove, setPendingMove] = useState<PendingMove | null>(null)
  const [treeFilter, setTreeFilter] = useState('')
  // Remember which folders the user opened/closed so the state survives refetches
  // (e.g. after a move / reorder) — keyed by path (#660).
  const [openOverrides, setOpenOverrides] = useState<Map<string, boolean>>(() => new Map())
  const tree = useMemo(() => buildPermalinkTree(records), [records])
  const filteredTree = useMemo(() => filterDirectoryTree(tree, treeFilter), [tree, treeFilter])

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

  const requestMove = (payload: DirectoryDragPayload, targetPath: string) => {
    // Affected = the dragged record plus its descendants (their URLs all change).
    const affectedCount = records.filter(
      (record) =>
        record.permalink === payload.permalink ||
        record.permalink.startsWith(`${payload.permalink}/`),
    ).length
    setPendingMove({
      id: payload.id,
      label: payload.label,
      newPermalink: moveTargetPermalink(payload, targetPath),
      affectedCount,
    })
  }

  const confirmMove = () => {
    if (pendingMove === null) {
      return
    }
    moveMutation.mutate(
      { id: pendingMove.id, permalink: pendingMove.newPermalink },
      {
        onSuccess: () => {
          showToast(t('admin.entityRecords.directory.move.success'), 'success')
          setPendingMove(null)
        },
        onError: (error) => {
          showToast(error.title, 'error')
          setPendingMove(null)
        },
      },
    )
  }

  const reorderSiblings = (ids: number[]) => {
    reorderMutation.mutate({ ids })
  }

  const toggleOpen = (path: string, currentlyOpen: boolean) => {
    setOpenOverrides((prev) => {
      const next = new Map(prev)
      next.set(path, !currentlyOpen)
      return next
    })
  }

  return (
    <div>
      {truncated ? (
        <p className="mb-stack-sm font-sans text-caption text-text-muted">
          {t('admin.entityRecords.directory.truncated')}
        </p>
      ) : null}
      {/* Tree quick-filter (#659): instant client-side path/title filter over the loaded tree. */}
      <div className="relative mb-stack-sm">
        <input
          type="search"
          value={treeFilter}
          onChange={(event) => {
            setTreeFilter(event.target.value)
          }}
          placeholder={t('admin.entityRecords.directory.filter.placeholder')}
          aria-label={t('admin.entityRecords.directory.filter.placeholder')}
          className="w-full rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:shadow-focus focus-visible:outline-none"
        />
        {treeFilter !== '' ? (
          <button
            type="button"
            onClick={() => {
              setTreeFilter('')
            }}
            aria-label={t('admin.entityRecords.directory.filter.clear')}
            className="absolute right-2 top-1/2 -translate-y-1/2 rounded p-0.5 text-text-muted hover:text-text-primary"
          >
            ✕
          </button>
        ) : null}
      </div>

      {treeFilter !== '' && filteredTree.length === 0 ? (
        <p className="font-sans text-body text-text-muted">
          {t('admin.entityRecords.directory.filter.noMatches', { query: treeFilter })}
        </p>
      ) : (
        <ul
          className="flex flex-col gap-stack-xs"
          aria-label={t('admin.entityRecords.view.directory')}
        >
          {filteredTree.map((node) => (
            <DirectoryNodeRow
              key={node.path}
              node={node}
              siblings={filteredTree}
              entityTypeSlug={entityTypeSlug}
              depth={0}
              query={treeFilter}
              forceOpen={treeFilter !== ''}
              openOverrides={openOverrides}
              onCreateHere={onCreateHere}
              onRequestMove={requestMove}
              onReorder={reorderSiblings}
              onToggle={toggleOpen}
            />
          ))}
        </ul>
      )}

      <ConfirmDialog
        open={pendingMove !== null}
        title={t('admin.entityRecords.directory.move.confirmTitle')}
        description={
          pendingMove === null
            ? undefined
            : pendingMove.affectedCount === 1
              ? t('admin.entityRecords.directory.move.confirmBody.one', {
                  label: pendingMove.label,
                  target: pendingMove.newPermalink,
                })
              : t('admin.entityRecords.directory.move.confirmBody.other', {
                  label: pendingMove.label,
                  target: pendingMove.newPermalink,
                  count: pendingMove.affectedCount,
                })
        }
        confirmLabel={t('admin.entityRecords.directory.move.confirm')}
        cancelLabel={t('common.actions.cancel')}
        isPending={moveMutation.isPending}
        errorDetail={moveMutation.error?.title ?? null}
        onConfirm={confirmMove}
        onCancel={() => {
          setPendingMove(null)
        }}
      />
    </div>
  )
}

function DirectoryNodeRow({
  node,
  siblings,
  entityTypeSlug,
  depth,
  query,
  forceOpen,
  openOverrides,
  onCreateHere,
  onRequestMove,
  onReorder,
  onToggle,
}: {
  node: DirectoryNode
  /** The sibling array this node belongs to (for manual up/down reorder). */
  siblings: DirectoryNode[]
  entityTypeSlug: string
  depth: number
  query: string
  forceOpen: boolean
  /** Persisted per-path open/closed overrides (#660). */
  openOverrides: Map<string, boolean>
  onCreateHere: (permalinkPrefix: string) => void
  onRequestMove: (payload: DirectoryDragPayload, targetPath: string) => void
  onReorder: (orderedRecordIds: number[]) => void
  onToggle: (path: string, currentlyOpen: boolean) => void
}) {
  const { t, locale } = useTranslation()
  // Initially expand only the top level; remember user toggles across refetches (#657, #660).
  const open = openOverrides.get(node.path) ?? depth === 0
  const [isDropTarget, setIsDropTarget] = useState(false)
  const hasChildren = node.children.length > 0
  const record = node.record
  // A tree filter forces every surviving branch open so matches are always shown.
  const isOpen = forceOpen || open

  // Manual reorder (#659) operates on RECORD siblings only (pure folders have no
  // menu_order); disabled while a filter is active to avoid reordering a subset.
  const recordSiblings = siblings.filter((sibling) => sibling.record !== null)
  const recordIndex = recordSiblings.findIndex((sibling) => sibling.record?.id === record?.id)
  const canReorder =
    record !== null && query === '' && recordSiblings.length > 1 && recordIndex !== -1

  const reorderTo = (delta: number) => {
    const ids = recordSiblings
      .map((sibling) => sibling.record?.id)
      .filter((id): id is number => id !== undefined)
    onReorder(moveInOrder(ids, recordIndex, delta))
  }

  const handleDragOver = (event: DragEvent<HTMLElement>) => {
    const payload = getDirectoryDragPayload()
    if (payload === null || !canDropInto(payload, node.path)) {
      return
    }
    event.preventDefault()
    event.stopPropagation()
    setIsDropTarget(true)
  }

  const handleDrop = (event: DragEvent<HTMLElement>) => {
    const payload = getDirectoryDragPayload()
    setIsDropTarget(false)
    if (payload === null || !canDropInto(payload, node.path)) {
      return
    }
    event.preventDefault()
    event.stopPropagation()
    onRequestMove(payload, node.path)
    clearDirectoryDragPayload()
  }

  return (
    <li>
      <div
        className={`flex items-center gap-inline-sm rounded-md py-stack-xs pr-inline-sm ${
          isDropTarget
            ? 'bg-surface-raised ring-2 ring-inset ring-accent'
            : 'hover:bg-surface-raised'
        }`}
        style={{ paddingLeft: depth * 20 + 8 }}
        onDragOver={handleDragOver}
        onDragLeave={() => {
          setIsDropTarget(false)
        }}
        onDrop={handleDrop}
      >
        {hasChildren ? (
          <button
            type="button"
            onClick={() => {
              onToggle(node.path, open)
            }}
            aria-expanded={isOpen}
            aria-label={
              isOpen
                ? t('admin.entityRecords.directory.collapse')
                : t('admin.entityRecords.directory.expand')
            }
            className="shrink-0 rounded font-mono text-caption text-text-muted focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-accent"
          >
            {isOpen ? '▾' : '▸'}
          </button>
        ) : (
          <span aria-hidden="true" className="shrink-0 font-mono text-caption text-text-muted">
            ·
          </span>
        )}

        {record !== null ? (
          <>
            <span
              draggable
              onDragStart={(event) => {
                setDirectoryDragPayload({
                  id: record.id,
                  permalink: record.permalink,
                  label: record.label,
                })
                event.dataTransfer.effectAllowed = 'move'
              }}
              onDragEnd={() => {
                clearDirectoryDragPayload()
              }}
              aria-label={t('admin.entityRecords.directory.dragHandle', { label: record.label })}
              className="shrink-0 cursor-grab select-none font-mono text-caption text-text-muted hover:text-text-primary"
            >
              ⠿
            </span>
            <Link
              to={`/admin/${entityTypeSlug}/${String(record.id)}`}
              className="truncate font-sans text-body text-text-primary hover:text-accent"
            >
              {highlightMatch(record.label, query)}
            </Link>
            {hasChildren ? (
              <span className="shrink-0 font-mono text-caption text-text-muted">
                ({node.children.length})
              </span>
            ) : null}
            <StatusBadge status={record.status}>
              {t(`admin.entityStatus.status.${record.status}`)}
            </StatusBadge>
          </>
        ) : (
          <>
            <span className="font-sans text-body font-medium text-text-muted">
              {highlightMatch(node.segment, query)}/
            </span>
            <span className="shrink-0 font-mono text-caption text-text-muted">
              ({node.children.length})
            </span>
          </>
        )}

        <div className="ml-auto flex shrink-0 items-center gap-inline-sm">
          {record !== null && formatDate(record.updatedAt, locale) !== '' ? (
            <span className="font-sans text-caption text-text-muted">
              {t('admin.entityRecords.list.item.updatedAt', {
                date: formatDate(record.updatedAt, locale),
              })}
            </span>
          ) : null}
          <code title={node.path} className="truncate font-mono text-caption text-text-muted">
            {highlightMatch(node.path, query)}
          </code>
          {canReorder ? (
            <>
              <button
                type="button"
                disabled={recordIndex === 0}
                onClick={() => {
                  reorderTo(-1)
                }}
                aria-label={t('admin.entityRecords.directory.moveUp')}
                title={t('admin.entityRecords.directory.moveUp')}
                className="shrink-0 rounded px-0.5 font-mono text-caption text-text-muted hover:text-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-accent disabled:cursor-not-allowed disabled:opacity-30"
              >
                ▲
              </button>
              <button
                type="button"
                disabled={recordIndex === recordSiblings.length - 1}
                onClick={() => {
                  reorderTo(1)
                }}
                aria-label={t('admin.entityRecords.directory.moveDown')}
                title={t('admin.entityRecords.directory.moveDown')}
                className="shrink-0 rounded px-0.5 font-mono text-caption text-text-muted hover:text-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-accent disabled:cursor-not-allowed disabled:opacity-30"
              >
                ▼
              </button>
            </>
          ) : null}
          <button
            type="button"
            onClick={() => {
              onCreateHere(`${node.path}/`)
            }}
            aria-label={t('admin.entityRecords.directory.newHere', { path: node.path })}
            title={t('admin.entityRecords.directory.newHere', { path: node.path })}
            className="shrink-0 rounded px-1 font-mono text-body text-text-muted hover:bg-surface-raised hover:text-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-accent"
          >
            +
          </button>
        </div>
      </div>

      {hasChildren && isOpen ? (
        <ul className="flex flex-col gap-stack-xs">
          {node.children.map((child) => (
            <DirectoryNodeRow
              key={child.path}
              node={child}
              siblings={node.children}
              entityTypeSlug={entityTypeSlug}
              depth={depth + 1}
              query={query}
              forceOpen={forceOpen}
              openOverrides={openOverrides}
              onCreateHere={onCreateHere}
              onRequestMove={onRequestMove}
              onReorder={onReorder}
              onToggle={onToggle}
            />
          ))}
        </ul>
      ) : null}
    </li>
  )
}

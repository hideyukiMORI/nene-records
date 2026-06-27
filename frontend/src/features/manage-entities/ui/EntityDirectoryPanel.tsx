import { type DragEvent, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useMoveEntity } from '@/entities/entity'
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
} from '../lib/build-permalink-tree'
import {
  canDropInto,
  clearDirectoryDragPayload,
  type DirectoryDragPayload,
  getDirectoryDragPayload,
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
  const [pendingMove, setPendingMove] = useState<PendingMove | null>(null)
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
          <DirectoryNodeRow
            key={node.path}
            node={node}
            entityTypeSlug={entityTypeSlug}
            depth={0}
            onCreateHere={onCreateHere}
            onRequestMove={requestMove}
          />
        ))}
      </ul>

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
  entityTypeSlug,
  depth,
  onCreateHere,
  onRequestMove,
}: {
  node: DirectoryNode
  entityTypeSlug: string
  depth: number
  onCreateHere: (permalinkPrefix: string) => void
  onRequestMove: (payload: DirectoryDragPayload, targetPath: string) => void
}) {
  const { t, locale } = useTranslation()
  // Initially expand only the top level — a deep tree is otherwise a wall of rows (#657).
  const [open, setOpen] = useState(depth === 0)
  const [isDropTarget, setIsDropTarget] = useState(false)
  const hasChildren = node.children.length > 0
  const record = node.record

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
              {record.label}
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
            <span className="font-sans text-body font-medium text-text-muted">{node.segment}/</span>
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
          <code className="truncate font-mono text-caption text-text-muted">{node.path}</code>
          <button
            type="button"
            onClick={() => {
              onCreateHere(`${node.path}/`)
            }}
            aria-label={t('admin.entityRecords.directory.newHere', { path: node.path })}
            title={t('admin.entityRecords.directory.newHere', { path: node.path })}
            className="shrink-0 rounded px-1 font-mono text-body text-text-muted hover:bg-surface-raised hover:text-accent"
          >
            +
          </button>
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
              onCreateHere={onCreateHere}
              onRequestMove={onRequestMove}
            />
          ))}
        </ul>
      ) : null}
    </li>
  )
}

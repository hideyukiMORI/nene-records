import type { AdminComment } from '@/entities/comment'
import { useAdminCommentList, useApproveComment, useDeleteComment } from '@/entities/comment'
import { useTranslation } from '@/shared/i18n'
import { Button, ConfirmDialog, EmptyState, Stack, Text } from '@/shared/ui'
import { useToast } from '@/shared/ui'
import { useState } from 'react'

export function ManageCommentsView() {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const commentsQuery = useAdminCommentList()
  const approveComment = useApproveComment()
  const deleteComment = useDeleteComment()

  const [deleteTarget, setDeleteTarget] = useState<AdminComment | null>(null)

  function handleApprove(comment: AdminComment) {
    approveComment.mutate(comment.id, {
      onSuccess: () => {
        showToast(t('admin.comments.approveSuccess'), 'success')
      },
    })
  }

  function handleDeleteConfirm() {
    if (deleteTarget === null) return
    const id = deleteTarget.id
    setDeleteTarget(null)
    deleteComment.mutate(id, {
      onSuccess: () => {
        showToast(t('admin.comments.deleteSuccess'), 'success')
      },
    })
  }

  if (commentsQuery.isLoading) {
    return <Text muted>{t('admin.comments.loading')}</Text>
  }

  if (commentsQuery.isError) {
    return (
      <Stack gap="sm">
        <Text muted>{t('admin.comments.loadError')}</Text>
        <Button
          variant="secondary"
          onClick={() => {
            void commentsQuery.refetch()
          }}
        >
          {t('common.actions.retry')}
        </Button>
      </Stack>
    )
  }

  const items = commentsQuery.data?.items ?? []

  return (
    <>
      {items.length === 0 ? (
        <EmptyState
          title={t('admin.comments.empty')}
          description={t('admin.comments.empty.description')}
        />
      ) : (
        <Stack gap="md">
          {items.map((comment) => (
            <div
              key={comment.id}
              className="rounded-lg border border-border bg-surface-raised px-inline-md py-stack-sm"
            >
              <Stack gap="sm">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <Stack gap="xs">
                    <div className="flex items-baseline gap-2">
                      <Text variant="heading-sm">{comment.authorName}</Text>
                      <span className="font-sans text-caption text-text-muted">
                        {comment.authorEmail}
                      </span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="font-sans text-caption text-text-muted">
                        {t('admin.comments.entityId', { id: String(comment.entityId) })}
                      </span>
                      <span className="font-sans text-caption text-text-muted">·</span>
                      <time
                        dateTime={comment.createdAt}
                        className="font-sans text-caption text-text-muted"
                      >
                        {new Date(comment.createdAt).toLocaleString()}
                      </time>
                      {comment.isApproved ? (
                        <span className="rounded-full bg-success/15 px-2 py-0.5 font-sans text-caption font-medium text-success">
                          {t('admin.comments.approved')}
                        </span>
                      ) : (
                        <span className="rounded-full bg-warning/15 px-2 py-0.5 font-sans text-caption font-medium text-warning">
                          {t('admin.comments.pending')}
                        </span>
                      )}
                    </div>
                  </Stack>
                  <div className="flex gap-2">
                    {!comment.isApproved ? (
                      <Button
                        variant="secondary"
                        size="sm"
                        disabled={approveComment.isPending}
                        onClick={() => {
                          handleApprove(comment)
                        }}
                      >
                        {t('admin.comments.approve')}
                      </Button>
                    ) : null}
                    <Button
                      variant="danger"
                      size="sm"
                      disabled={deleteComment.isPending}
                      onClick={() => {
                        setDeleteTarget(comment)
                      }}
                    >
                      {t('admin.comments.delete')}
                    </Button>
                  </div>
                </div>
                <Text>{comment.body}</Text>
              </Stack>
            </div>
          ))}
        </Stack>
      )}

      <ConfirmDialog
        open={deleteTarget !== null}
        title={t('admin.comments.deleteConfirmTitle')}
        description={t('admin.comments.deleteConfirmDescription', {
          name: deleteTarget?.authorName ?? '',
        })}
        confirmLabel={t('admin.comments.delete')}
        isPending={deleteComment.isPending}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          setDeleteTarget(null)
        }}
      />
    </>
  )
}

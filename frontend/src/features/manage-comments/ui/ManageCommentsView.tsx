import type { AdminComment } from '@/entities/comment'
import { useTranslation } from '@/shared/i18n'
import {
  Button,
  Card,
  ConfirmDialog,
  EmptyState,
  ErrorState,
  LoadingState,
  Stack,
  Text,
} from '@/shared/ui'

interface ManageCommentsViewProps {
  comments: AdminComment[]
  isLoading: boolean
  isError: boolean
  isApproving: boolean
  isDeleting: boolean
  deleteTarget: AdminComment | null
  onRetry: () => void
  onApprove: (comment: AdminComment) => void
  onDeleteRequest: (comment: AdminComment) => void
  onDeleteConfirm: () => void
  onDeleteCancel: () => void
}

export function ManageCommentsView({
  comments,
  isLoading,
  isError,
  isApproving,
  isDeleting,
  deleteTarget,
  onRetry,
  onApprove,
  onDeleteRequest,
  onDeleteConfirm,
  onDeleteCancel,
}: ManageCommentsViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.comments.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        message={t('admin.comments.loadError')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  return (
    <>
      {comments.length === 0 ? (
        <EmptyState
          title={t('admin.comments.empty')}
          description={t('admin.comments.empty.description')}
        />
      ) : (
        <Stack gap="md">
          {comments.map((comment) => (
            <Card key={comment.id} padding="row">
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
                        disabled={isApproving}
                        onClick={() => {
                          onApprove(comment)
                        }}
                      >
                        {t('admin.comments.approve')}
                      </Button>
                    ) : null}
                    <Button
                      variant="danger"
                      size="sm"
                      disabled={isDeleting}
                      onClick={() => {
                        onDeleteRequest(comment)
                      }}
                    >
                      {t('admin.comments.delete')}
                    </Button>
                  </div>
                </div>
                <Text>{comment.body}</Text>
              </Stack>
            </Card>
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
        isPending={isDeleting}
        onConfirm={onDeleteConfirm}
        onCancel={onDeleteCancel}
      />
    </>
  )
}

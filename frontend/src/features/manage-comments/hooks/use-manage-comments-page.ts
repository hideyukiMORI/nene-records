import { useState } from 'react'
import type { AdminComment } from '@/entities/comment'
import { useAdminCommentList, useApproveComment, useDeleteComment } from '@/entities/comment'
import { useTranslation } from '@/shared/i18n'
import { useToast } from '@/shared/ui'

export interface ManageCommentsPageState {
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

export function useManageCommentsPage(): ManageCommentsPageState {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const commentsQuery = useAdminCommentList()
  const approveComment = useApproveComment()
  const deleteComment = useDeleteComment()

  const [deleteTarget, setDeleteTarget] = useState<AdminComment | null>(null)

  function onApprove(comment: AdminComment) {
    approveComment.mutate(comment.id, {
      onSuccess: () => {
        showToast(t('admin.comments.approveSuccess'), 'success')
      },
      onError: () => {
        showToast(t('admin.comments.approveError'), 'error')
      },
    })
  }

  function onDeleteConfirm() {
    if (deleteTarget === null) return
    const id = deleteTarget.id
    setDeleteTarget(null)
    deleteComment.mutate(id, {
      onSuccess: () => {
        showToast(t('admin.comments.deleteSuccess'), 'success')
      },
      onError: () => {
        showToast(t('admin.comments.deleteError'), 'error')
      },
    })
  }

  return {
    comments: commentsQuery.data?.items ?? [],
    isLoading: commentsQuery.isLoading,
    isError: commentsQuery.isError,
    isApproving: approveComment.isPending,
    isDeleting: deleteComment.isPending,
    deleteTarget,
    onRetry: () => {
      void commentsQuery.refetch()
    },
    onApprove,
    onDeleteRequest: setDeleteTarget,
    onDeleteConfirm,
    onDeleteCancel: () => {
      setDeleteTarget(null)
    },
  }
}

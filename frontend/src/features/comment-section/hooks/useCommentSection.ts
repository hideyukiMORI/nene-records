import { useState } from 'react'
import type { Comment } from '@/entities/comment'
import { useCommentList, usePostComment } from '@/entities/comment'

export interface CommentSectionState {
  comments: Comment[]
  isLoading: boolean
  isError: boolean
  authorName: string
  authorEmail: string
  body: string
  submitted: boolean
  isPending: boolean
  isPostError: boolean
  onAuthorNameChange: (value: string) => void
  onAuthorEmailChange: (value: string) => void
  onBodyChange: (value: string) => void
  onSubmit: (e: React.SyntheticEvent) => void
}

export function useCommentSection(entityId: number): CommentSectionState {
  const commentsQuery = useCommentList(entityId)
  const postComment = usePostComment()

  const [authorName, setAuthorName] = useState('')
  const [authorEmail, setAuthorEmail] = useState('')
  const [body, setBody] = useState('')
  const [submitted, setSubmitted] = useState(false)

  function onSubmit(e: React.SyntheticEvent) {
    e.preventDefault()
    setSubmitted(false)
    postComment.mutate(
      {
        entityId,
        authorName: authorName.trim(),
        authorEmail: authorEmail.trim(),
        body: body.trim(),
      },
      {
        onSuccess: () => {
          setAuthorName('')
          setAuthorEmail('')
          setBody('')
          setSubmitted(true)
        },
      },
    )
  }

  return {
    comments: commentsQuery.data?.items ?? [],
    isLoading: commentsQuery.isLoading,
    isError: commentsQuery.isError,
    authorName,
    authorEmail,
    body,
    submitted,
    isPending: postComment.isPending,
    isPostError: postComment.isError,
    onAuthorNameChange: setAuthorName,
    onAuthorEmailChange: setAuthorEmail,
    onBodyChange: setBody,
    onSubmit,
  }
}

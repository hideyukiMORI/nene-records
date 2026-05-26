import { useCommentList, usePostComment } from '@/entities/comment'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'
import { useState } from 'react'

interface Props {
  entityId: number
}

export function CommentSection({ entityId }: Props) {
  const { t } = useTranslation()
  const commentsQuery = useCommentList(entityId)
  const postComment = usePostComment()

  const [authorName, setAuthorName] = useState('')
  const [authorEmail, setAuthorEmail] = useState('')
  const [body, setBody] = useState('')
  const [submitted, setSubmitted] = useState(false)

  function handleSubmit(e: React.SyntheticEvent) {
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

  const comments = commentsQuery.data?.items ?? []

  return (
    <Stack gap="lg">
      <Text as="h2" variant="heading-sm">
        {t('public.comments.title')}
      </Text>

      {/* Comment list */}
      {commentsQuery.isLoading ? (
        <Text muted>{t('public.comments.loading')}</Text>
      ) : commentsQuery.isError ? (
        <Text muted>{t('public.comments.loadError')}</Text>
      ) : comments.length === 0 ? (
        <Text muted>{t('public.comments.empty')}</Text>
      ) : (
        <Stack gap="md">
          {comments.map((comment) => (
            <div
              key={comment.id}
              className="rounded-lg border border-border bg-surface-raised px-inline-md py-stack-sm"
            >
              <Stack gap="xs">
                <div className="flex items-baseline gap-2">
                  <Text variant="heading-sm">{comment.authorName}</Text>
                  <Text muted>
                    <time dateTime={comment.createdAt}>
                      {new Date(comment.createdAt).toLocaleDateString()}
                    </time>
                  </Text>
                </div>
                <Text>{comment.body}</Text>
              </Stack>
            </div>
          ))}
        </Stack>
      )}

      {/* Post comment form */}
      <Stack gap="md">
        <Text as="h3" variant="heading-sm">
          {t('public.comments.form.title')}
        </Text>
        {submitted ? (
          <p className="rounded-md bg-success/10 px-inline-md py-stack-sm font-sans text-body text-success">
            {t('public.comments.form.success')}
          </p>
        ) : null}
        <form onSubmit={handleSubmit}>
          <Stack gap="md">
            <Input
              id="comment-author-name"
              label={t('public.comments.form.authorName')}
              type="text"
              value={authorName}
              onChange={(e) => {
                setAuthorName(e.target.value)
              }}
              disabled={postComment.isPending}
            />
            <Input
              id="comment-author-email"
              label={t('public.comments.form.authorEmail')}
              type="email"
              value={authorEmail}
              onChange={(e) => {
                setAuthorEmail(e.target.value)
              }}
              disabled={postComment.isPending}
            />
            <div className="flex flex-col gap-stack-xs">
              <label
                htmlFor="comment-body"
                className="font-sans text-body font-medium text-text-primary"
              >
                {t('public.comments.form.body')}
              </label>
              <textarea
                id="comment-body"
                value={body}
                onChange={(e) => {
                  setBody(e.target.value)
                }}
                disabled={postComment.isPending}
                rows={4}
                className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>
            {postComment.isError ? <Text muted>{t('public.comments.form.error')}</Text> : null}
            <div>
              <Button type="submit" disabled={postComment.isPending}>
                {postComment.isPending
                  ? t('public.comments.form.submitting')
                  : t('public.comments.form.submit')}
              </Button>
            </div>
          </Stack>
        </form>
      </Stack>
    </Stack>
  )
}

import type { Comment } from '@/entities/comment'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

interface Props {
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

export function CommentSection({
  comments,
  isLoading,
  isError,
  authorName,
  authorEmail,
  body,
  submitted,
  isPending,
  isPostError,
  onAuthorNameChange,
  onAuthorEmailChange,
  onBodyChange,
  onSubmit,
}: Props) {
  const { t } = useTranslation()

  return (
    <Stack gap="lg">
      <Text as="h2" variant="heading-sm">
        {t('public.comments.title')}
      </Text>

      {/* Comment list */}
      {isLoading ? (
        <Text muted>{t('public.comments.loading')}</Text>
      ) : isError ? (
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
        <form onSubmit={onSubmit}>
          <Stack gap="md">
            <Input
              id="comment-author-name"
              label={t('public.comments.form.authorName')}
              type="text"
              value={authorName}
              onChange={(e) => {
                onAuthorNameChange(e.target.value)
              }}
              disabled={isPending}
            />
            <Input
              id="comment-author-email"
              label={t('public.comments.form.authorEmail')}
              type="email"
              value={authorEmail}
              onChange={(e) => {
                onAuthorEmailChange(e.target.value)
              }}
              disabled={isPending}
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
                  onBodyChange(e.target.value)
                }}
                disabled={isPending}
                rows={4}
                className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>
            {isPostError ? <Text muted>{t('public.comments.form.error')}</Text> : null}
            <div>
              <Button type="submit" disabled={isPending}>
                {isPending
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

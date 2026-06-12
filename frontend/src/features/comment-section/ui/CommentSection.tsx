import type { Comment } from '@/entities/comment'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Input, Stack, Text, Textarea } from '@/shared/ui'

interface Props {
  comments: Comment[]
  isLoading: boolean
  isError: boolean
  authorName: string
  authorEmail: string
  body: string
  honeypot: string
  submitted: boolean
  isPending: boolean
  isPostError: boolean
  onAuthorNameChange: (value: string) => void
  onAuthorEmailChange: (value: string) => void
  onBodyChange: (value: string) => void
  onHoneypotChange: (value: string) => void
  onSubmit: (e: React.SyntheticEvent) => void
}

export function CommentSection({
  comments,
  isLoading,
  isError,
  authorName,
  authorEmail,
  body,
  honeypot,
  submitted,
  isPending,
  isPostError,
  onAuthorNameChange,
  onAuthorEmailChange,
  onBodyChange,
  onHoneypotChange,
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
            <Card key={comment.id} padding="row">
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
            </Card>
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
            {/*
              Honeypot: hidden from real users (and assistive tech) but visible to naive bots
              that auto-fill every field. A non-empty value is rejected server-side (#264).
            */}
            <div
              aria-hidden="true"
              style={{
                position: 'absolute',
                left: '-9999px',
                height: 0,
                width: 0,
                overflow: 'hidden',
              }}
            >
              <label htmlFor="comment-website">Website</label>
              <input
                id="comment-website"
                name="website"
                type="text"
                tabIndex={-1}
                autoComplete="off"
                value={honeypot}
                onChange={(e) => {
                  onHoneypotChange(e.target.value)
                }}
              />
            </div>
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
            <Textarea
              id="comment-body"
              label={t('public.comments.form.body')}
              value={body}
              onChange={(e) => {
                onBodyChange(e.target.value)
              }}
              disabled={isPending}
              rows={4}
            />
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

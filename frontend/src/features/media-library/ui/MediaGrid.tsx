import type { Media } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, EmptyState, ErrorState, LoadingState, ResponsiveImage } from '@/shared/ui'
import { IconCopy, IconImage } from '@/shared/ui/icons/Icons'

export interface MediaGridProps {
  items: Media[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  copiedId: number | null
  onRetry: () => void
  onCopy: (media: Media) => void
  onUpdateAlt: (media: Media, altText: string) => void
  onDelete: (media: Media) => void
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${String(bytes)} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function isImageMime(mimeType: string): boolean {
  return mimeType.startsWith('image/')
}

export function MediaGrid({
  items,
  isLoading,
  isError,
  errorTitle,
  copiedId,
  onRetry,
  onCopy,
  onUpdateAlt,
  onDelete,
}: MediaGridProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.media.list.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        message={errorTitle ?? t('admin.media.list.error')}
        onRetry={onRetry}
        retryLabel={t('admin.media.list.retry')}
      />
    )
  }

  if (items.length === 0) {
    return (
      <EmptyState
        title={t('admin.media.list.empty.title')}
        description={t('admin.media.list.empty.description')}
      />
    )
  }

  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
      {items.map((media) => (
        <Card
          key={media.id}
          padding="none"
          className="group relative flex flex-col overflow-hidden"
        >
          {/* Thumbnail */}
          <div className="flex h-32 items-center justify-center bg-surface-overlay">
            {isImageMime(media.mimeType) ? (
              <ResponsiveImage
                src={media.url}
                alt={media.altText ?? media.originalName}
                sizes="(max-width: 640px) 50vw, 20vw"
                className="h-full w-full object-cover"
              />
            ) : (
              <IconImage size={32} className="text-text-muted" />
            )}
          </div>

          {/* Info */}
          <div className="flex flex-col gap-0.5 p-2">
            <div
              className="truncate text-xs font-medium text-text-primary"
              title={media.originalName}
            >
              {media.originalName}
            </div>
            <div className="text-caption text-text-muted">
              {formatBytes(media.size)}
              {media.width !== null && media.height !== null
                ? ` · ${String(media.width)}×${String(media.height)}`
                : ''}
            </div>
            <input
              type="text"
              defaultValue={media.altText ?? ''}
              placeholder={t('admin.media.alt.placeholder')}
              aria-label={t('admin.media.alt.label')}
              onBlur={(e) => {
                onUpdateAlt(media, e.target.value)
              }}
              className="mt-1 w-full rounded border border-border bg-surface px-1.5 py-1 text-caption text-text-primary placeholder:text-text-muted focus:border-accent focus:outline-none"
            />
          </div>

          {/* Actions overlay */}
          <div className="absolute inset-x-0 bottom-0 flex translate-y-full gap-1 bg-surface-overlay/90 p-1.5 transition-transform duration-150 group-hover:translate-y-0">
            <Button
              variant="secondary"
              size="sm"
              title={copiedId === media.id ? t('admin.media.copied') : t('admin.media.copy')}
              onClick={() => {
                onCopy(media)
              }}
              className="flex-1"
            >
              <IconCopy size={12} />
              <span className="ml-1 text-caption">
                {copiedId === media.id ? t('admin.media.copied') : t('admin.media.copy')}
              </span>
            </Button>
            <Button
              variant="danger"
              size="sm"
              title={t('admin.media.delete')}
              onClick={() => {
                onDelete(media)
              }}
            >
              ×
            </Button>
          </div>
        </Card>
      ))}
    </div>
  )
}

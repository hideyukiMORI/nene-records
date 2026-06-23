import { useMediaList, type Media } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { ErrorState, LoadingState, Modal, ResponsiveImage, Text } from '@/shared/ui'
import { IconX } from '@/shared/ui/icons/Icons'

interface MediaSelectorModalProps {
  currentMediaId: string | null
  onSelect: (media: Media) => void
  onClose: () => void
}

/**
 * Picks an image from the media library (#486 S3). Reuses entities/media; lives in
 * this feature because shared/ui may not read entity data. Returns the chosen
 * Media; the caller stores its url (the metadata API is admin-only, so the
 * consumer renders the stored url directly — like the image field type).
 */
export function MediaSelectorModal({ currentMediaId, onSelect, onClose }: MediaSelectorModalProps) {
  const { t } = useTranslation()
  const { data, isLoading, isError, error, refetch } = useMediaList()
  const images = (data?.items ?? []).filter((item) => item.mimeType.startsWith('image/'))

  return (
    <Modal
      onClose={onClose}
      closeLabel={t('admin.blocks.media.close')}
      labelledBy="block-media-picker-title"
      panelClassName="max-w-2xl flex flex-col gap-stack-sm shadow-md"
    >
      <div className="flex items-center justify-between">
        <Text as="h2" id="block-media-picker-title" variant="heading-sm">
          {t('admin.blocks.media.pickerTitle')}
        </Text>
        <button
          type="button"
          onClick={onClose}
          aria-label={t('admin.blocks.media.close')}
          className="rounded p-1 text-text-muted hover:text-text-primary"
        >
          <IconX size={18} />
        </button>
      </div>

      {isLoading ? (
        <LoadingState>{t('admin.blocks.media.loading')}</LoadingState>
      ) : isError ? (
        <ErrorState
          message={error.title}
          onRetry={() => void refetch()}
          retryLabel={t('common.actions.retry')}
        />
      ) : images.length === 0 ? (
        <Text muted>{t('admin.blocks.media.empty')}</Text>
      ) : (
        <ul className="grid max-h-96 grid-cols-3 gap-inline-sm overflow-y-auto">
          {images.map((item) => {
            const selected = currentMediaId === String(item.id)
            return (
              <li key={item.id}>
                <button
                  type="button"
                  onClick={() => {
                    onSelect(item)
                  }}
                  className={[
                    'flex w-full flex-col gap-stack-xs rounded-md border p-1 text-left',
                    selected
                      ? 'border-accent ring-2 ring-accent'
                      : 'border-border hover:border-accent',
                  ].join(' ')}
                >
                  <ResponsiveImage
                    src={item.url}
                    alt={item.altText ?? item.originalName}
                    sizes="200px"
                    className="h-24 w-full rounded object-cover"
                  />
                  <span className="truncate font-sans text-caption text-text-muted">
                    {item.originalName}
                  </span>
                </button>
              </li>
            )
          })}
        </ul>
      )}
    </Modal>
  )
}

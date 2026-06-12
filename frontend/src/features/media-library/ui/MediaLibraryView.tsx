import type { Media } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { ConfirmDialog, Stack, Text } from '@/shared/ui'
import { MediaDropzone } from './MediaDropzone'
import { MediaGrid } from './MediaGrid'

export interface MediaLibraryViewProps {
  items: Media[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null

  isUploading: boolean
  uploadErrorTitle: string | null
  onUpload: (files: FileList | File[]) => Promise<void>

  copiedId: number | null
  onCopy: (media: Media) => void
  onUpdateAlt: (media: Media, altText: string) => void

  deleteTarget: Media | null
  isDeleting: boolean
  onRequestDelete: (media: Media) => void
  onCancelDelete: () => void
  onConfirmDelete: () => Promise<void>

  onRetry: () => void
}

export function MediaLibraryView({
  items,
  isLoading,
  isError,
  errorTitle,
  isUploading,
  uploadErrorTitle,
  onUpload,
  copiedId,
  onCopy,
  onUpdateAlt,
  deleteTarget,
  isDeleting,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
  onRetry,
}: MediaLibraryViewProps) {
  const { t } = useTranslation()

  return (
    <>
      <Stack gap="lg">
        <MediaDropzone
          isUploading={isUploading}
          uploadErrorTitle={uploadErrorTitle}
          onUpload={onUpload}
        />

        <Stack gap="sm">
          <Text as="h2" variant="heading-sm">
            {t('admin.media.list.title')}
          </Text>
          <MediaGrid
            items={items}
            isLoading={isLoading}
            isError={isError}
            errorTitle={errorTitle}
            copiedId={copiedId}
            onRetry={onRetry}
            onCopy={onCopy}
            onUpdateAlt={onUpdateAlt}
            onDelete={onRequestDelete}
          />
        </Stack>
      </Stack>

      <ConfirmDialog
        open={deleteTarget !== null}
        title={t('admin.media.delete.confirmTitle')}
        description={
          deleteTarget !== null
            ? t('admin.media.delete.confirmDescription', { name: deleteTarget.originalName })
            : undefined
        }
        confirmLabel={isDeleting ? t('admin.media.delete.deleting') : t('admin.media.delete')}
        isPending={isDeleting}
        onCancel={onCancelDelete}
        onConfirm={() => {
          void onConfirmDelete()
        }}
      />
    </>
  )
}

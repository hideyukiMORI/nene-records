import { useRef } from 'react'
import { useUploadMedia } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

interface FileFieldInputProps {
  id: string
  label: string
  value: string
  disabled: boolean
  onChange: (value: string) => void
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${String(bytes)} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function FileIcon({ mimeType }: { mimeType: string }) {
  if (mimeType.startsWith('image/')) {
    return <span aria-hidden="true">🖼</span>
  }
  if (mimeType === 'application/pdf') {
    return <span aria-hidden="true">📄</span>
  }
  if (
    mimeType === 'application/msword' ||
    mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ) {
    return <span aria-hidden="true">📝</span>
  }
  if (
    mimeType === 'application/vnd.ms-excel' ||
    mimeType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  ) {
    return <span aria-hidden="true">📊</span>
  }
  if (
    mimeType === 'application/vnd.ms-powerpoint' ||
    mimeType === 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
  ) {
    return <span aria-hidden="true">📽</span>
  }
  if (mimeType.startsWith('video/')) {
    return <span aria-hidden="true">🎬</span>
  }
  if (mimeType.startsWith('audio/')) {
    return <span aria-hidden="true">🎵</span>
  }
  if (mimeType === 'application/zip') {
    return <span aria-hidden="true">🗜</span>
  }
  return <span aria-hidden="true">📎</span>
}

export function FileFieldInput({ id, label, value, disabled, onChange }: FileFieldInputProps) {
  const { t } = useTranslation()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const uploadMutation = useUploadMedia()

  const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (file === undefined) return
    try {
      const media = await uploadMutation.mutateAsync(file)
      onChange(media.url)
    } catch {
      // error visible via uploadMutation.isError
    }
    if (fileInputRef.current !== null) {
      fileInputRef.current.value = ''
    }
  }

  const uploadedMedia = uploadMutation.isSuccess ? uploadMutation.data : null

  return (
    <Stack gap="xs">
      <label htmlFor={id} className="text-sm font-medium text-text-primary">
        {label}
      </label>
      <div className="flex items-center gap-inline-sm">
        <input
          id={id}
          type="text"
          value={value}
          disabled={disabled || uploadMutation.isPending}
          placeholder="/media/2026/05/..."
          onChange={(e) => {
            onChange(e.target.value)
          }}
          className="flex-1 rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
        />
        <Button
          type="button"
          variant="secondary"
          size="sm"
          disabled={disabled || uploadMutation.isPending}
          onClick={() => fileInputRef.current?.click()}
        >
          {uploadMutation.isPending
            ? t('admin.media.uploading')
            : t('admin.media.fileUploadButton')}
        </Button>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,video/mp4,video/webm,audio/mpeg,audio/wav,audio/ogg,application/zip,text/plain,text/csv"
          className="hidden"
          onChange={(e) => void handleFileChange(e)}
        />
      </div>
      {uploadMutation.isError && <Text muted>{t('admin.media.uploadError')}</Text>}
      {value !== '' && (
        <div className="mt-1 flex items-center gap-2 rounded-md border border-border bg-surface px-3 py-2 text-sm">
          {uploadedMedia !== null ? (
            <>
              <FileIcon mimeType={uploadedMedia.mimeType} />
              <span className="min-w-0 flex-1 truncate text-text-primary">
                {uploadedMedia.originalName}
              </span>
              <span className="shrink-0 text-text-muted">{formatBytes(uploadedMedia.size)}</span>
              <span className="shrink-0 text-text-muted">{uploadedMedia.mimeType}</span>
              <a
                href={value}
                target="_blank"
                rel="noopener noreferrer"
                className="shrink-0 text-accent hover:underline"
              >
                {t('admin.media.fileDownload')}
              </a>
            </>
          ) : (
            <>
              <span aria-hidden="true">📎</span>
              <a
                href={value}
                target="_blank"
                rel="noopener noreferrer"
                className="min-w-0 flex-1 truncate text-accent hover:underline"
              >
                {value}
              </a>
            </>
          )}
        </div>
      )}
    </Stack>
  )
}

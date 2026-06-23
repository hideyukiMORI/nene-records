import { useCallback, useRef, useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'
import { IconUpload } from '@/shared/ui/icons/Icons'

export interface MediaDropzoneProps {
  isUploading: boolean
  uploadErrorTitle: string | null
  onUpload: (files: FileList | File[]) => Promise<void>
}

export function MediaDropzone({ isUploading, uploadErrorTitle, onUpload }: MediaDropzoneProps) {
  const { t } = useTranslation()
  const inputRef = useRef<HTMLInputElement>(null)
  const [isDragging, setIsDragging] = useState(false)

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault()
      setIsDragging(false)
      if (e.dataTransfer.files.length > 0) {
        void onUpload(e.dataTransfer.files)
      }
    },
    [onUpload],
  )

  const handleFileChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.files && e.target.files.length > 0) {
        void onUpload(e.target.files)
        e.target.value = ''
      }
    },
    [onUpload],
  )

  return (
    <div>
      <button
        type="button"
        disabled={isUploading}
        onDragOver={(e) => {
          e.preventDefault()
          setIsDragging(true)
        }}
        onDragLeave={() => {
          setIsDragging(false)
        }}
        onDrop={handleDrop}
        onClick={() => {
          inputRef.current?.click()
        }}
        className={[
          'flex w-full cursor-pointer flex-col items-center gap-3 rounded-lg border-2 border-dashed p-8 transition-colors',
          isDragging
            ? 'border-accent bg-accent/5'
            : 'border-border bg-surface-raised hover:border-accent hover:bg-accent/5',
          isUploading ? 'cursor-not-allowed opacity-50' : '',
        ].join(' ')}
      >
        <IconUpload size={32} className="text-text-muted" />
        <div className="text-center">
          <Text variant="body">
            {isUploading ? t('admin.media.dropzone.uploading') : t('admin.media.dropzone.label')}
          </Text>
          <Text variant="caption" muted>
            {t('admin.media.dropzone.hint')}
          </Text>
        </div>
      </button>
      <input
        ref={inputRef}
        type="file"
        multiple
        accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,video/mp4,video/webm,audio/mpeg,audio/wav,audio/ogg,.zip,text/plain,text/csv"
        className="hidden"
        onChange={handleFileChange}
      />
      {uploadErrorTitle !== null ? (
        <Text muted className="mt-2 text-danger">
          {uploadErrorTitle}
        </Text>
      ) : null}
    </div>
  )
}

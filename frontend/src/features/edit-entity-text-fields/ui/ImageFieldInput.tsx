import { useRef } from 'react'
import { useUploadMedia } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { Button, ResponsiveImage, Stack, Text } from '@/shared/ui'

interface ImageFieldInputProps {
  id: string
  label: string
  value: string
  disabled: boolean
  onChange: (value: string) => void
}

export function ImageFieldInput({ id, label, value, disabled, onChange }: ImageFieldInputProps) {
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
    // reset file input so the same file can be re-selected
    if (fileInputRef.current !== null) {
      fileInputRef.current.value = ''
    }
  }

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
          className="flex-1 rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
        />
        <Button
          type="button"
          variant="secondary"
          size="sm"
          disabled={disabled || uploadMutation.isPending}
          onClick={() => fileInputRef.current?.click()}
        >
          {uploadMutation.isPending ? t('admin.media.uploading') : t('admin.media.uploadButton')}
        </Button>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
          className="hidden"
          onChange={(e) => void handleFileChange(e)}
        />
      </div>
      {uploadMutation.isError && <Text muted>{t('admin.media.uploadError')}</Text>}
      {value !== '' && (
        <ResponsiveImage
          src={value}
          alt={t('admin.media.imagePreview')}
          sizes="(max-width: 768px) 100vw, 24rem"
          className="mt-1 max-h-48 max-w-full rounded-md border border-border object-contain"
        />
      )}
    </Stack>
  )
}

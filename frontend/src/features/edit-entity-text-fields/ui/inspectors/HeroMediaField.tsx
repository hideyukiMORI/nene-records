import { useState } from 'react'
import { type Media } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, ResponsiveImage } from '@/shared/ui'
import { type HeroMedia } from '@/shared/lib/blocks-document'
import { MediaSelectorModal } from '../MediaSelectorModal'

interface HeroMediaFieldProps {
  idPrefix: string
  media: HeroMedia | undefined
  disabled: boolean
  onChange: (media: HeroMedia | undefined) => void
}

/** Library image picker + alt text for the hero art (#486 S3). */
export function HeroMediaField({ idPrefix, media, disabled, onChange }: HeroMediaFieldProps) {
  const { t } = useTranslation()
  const [pickerOpen, setPickerOpen] = useState(false)

  const handleSelect = (item: Media) => {
    onChange({
      mediaId: String(item.id),
      url: item.url,
      ...(item.altText !== null && item.altText !== '' ? { alt: item.altText } : {}),
    })
    setPickerOpen(false)
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <span className="font-sans text-caption font-medium text-text-primary">
        {t('admin.blocks.field.image')}
      </span>
      <div className="flex items-center gap-inline-sm">
        {media !== undefined ? (
          <ResponsiveImage
            src={media.url}
            alt={media.alt ?? ''}
            sizes="96px"
            className="h-16 w-24 rounded border border-border object-cover"
          />
        ) : (
          <span className="font-sans text-caption text-text-muted">
            {t('admin.blocks.media.none')}
          </span>
        )}
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled}
          onClick={() => {
            setPickerOpen(true)
          }}
        >
          {media !== undefined ? t('admin.blocks.media.change') : t('admin.blocks.media.select')}
        </Button>
        {media !== undefined ? (
          <Button
            variant="ghost"
            size="sm"
            disabled={disabled}
            onClick={() => {
              onChange(undefined)
            }}
          >
            {t('admin.blocks.media.remove')}
          </Button>
        ) : null}
      </div>
      {media !== undefined ? (
        <Input
          id={`${idPrefix}-media-alt`}
          label={t('admin.blocks.media.alt')}
          value={media.alt ?? ''}
          disabled={disabled}
          autoComplete="off"
          onChange={(event) => {
            onChange({ ...media, alt: event.target.value })
          }}
        />
      ) : null}
      {pickerOpen ? (
        <MediaSelectorModal
          currentMediaId={media?.mediaId ?? null}
          onSelect={handleSelect}
          onClose={() => {
            setPickerOpen(false)
          }}
        />
      ) : null}
    </div>
  )
}

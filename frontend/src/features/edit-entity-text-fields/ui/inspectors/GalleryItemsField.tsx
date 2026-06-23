import { useState } from 'react'
import { type Media } from '@/entities/media'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, ResponsiveImage } from '@/shared/ui'
import { IconChevronDown, IconChevronUp, IconX } from '@/shared/ui/icons/Icons'
import { type GalleryItem } from '@/shared/lib/blocks-document'
import { moveItem } from '@/shared/lib/move-item'
import { MediaSelectorModal } from '../MediaSelectorModal'

interface GalleryItemsFieldProps {
  idPrefix: string
  items: GalleryItem[]
  disabled: boolean
  error?: string | undefined
  onChange: (items: GalleryItem[]) => void
}

/** Repeater of gallery slides — each a library image + required alt (C4) + caption (#486 S4). */
export function GalleryItemsField({
  idPrefix,
  items,
  disabled,
  error,
  onChange,
}: GalleryItemsFieldProps) {
  const { t } = useTranslation()
  // null = closed, -1 = adding a new slide, >= 0 = replacing slide i's image
  const [pickFor, setPickFor] = useState<number | null>(null)

  const update = (index: number, patch: Partial<GalleryItem>) => {
    onChange(items.map((item, i) => (i === index ? { ...item, ...patch } : item)))
  }
  const move = (index: number, direction: -1 | 1) => {
    const next = moveItem(items, index, direction)
    if (next !== null) {
      onChange(next)
    }
  }
  const remove = (index: number) => {
    onChange(items.filter((_, i) => i !== index))
  }
  const handleSelect = (media: Media) => {
    if (pickFor === -1) {
      onChange([...items, { mediaId: String(media.id), url: media.url, alt: media.altText ?? '' }])
    } else if (pickFor !== null) {
      update(pickFor, { mediaId: String(media.id), url: media.url })
    }
    setPickFor(null)
  }

  return (
    <div className="flex flex-col gap-stack-sm">
      <span className="font-sans text-caption font-medium text-text-primary">
        {t('admin.blocks.field.images')}
      </span>
      {error !== undefined ? (
        <span role="alert" className="font-sans text-caption text-danger">
          {error}
        </span>
      ) : null}
      {items.map((item, index) => (
        <div
          key={`${item.mediaId}-${String(index)}`}
          className="flex flex-col gap-stack-xs rounded-md border border-border p-inline-sm"
        >
          <div className="flex items-center gap-inline-sm">
            <ResponsiveImage
              src={item.url}
              alt={item.alt}
              sizes="80px"
              className="h-12 w-16 rounded border border-border object-cover"
            />
            <Button
              variant="secondary"
              size="sm"
              disabled={disabled}
              onClick={() => {
                setPickFor(index)
              }}
            >
              {t('admin.blocks.media.change')}
            </Button>
            <span className="flex-1" />
            <button
              type="button"
              className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
              title={t('admin.blocks.moveUp')}
              disabled={disabled || index === 0}
              onClick={() => {
                move(index, -1)
              }}
            >
              <IconChevronUp size={15} />
            </button>
            <button
              type="button"
              className="rounded p-1 text-text-muted hover:text-text-primary disabled:opacity-40"
              title={t('admin.blocks.moveDown')}
              disabled={disabled || index === items.length - 1}
              onClick={() => {
                move(index, 1)
              }}
            >
              <IconChevronDown size={15} />
            </button>
            <button
              type="button"
              className="rounded p-1 text-text-muted hover:text-danger disabled:opacity-40"
              title={t('common.actions.delete')}
              disabled={disabled}
              onClick={() => {
                remove(index)
              }}
            >
              <IconX size={15} />
            </button>
          </div>
          <Input
            id={`${idPrefix}-item-${String(index)}-alt`}
            label={t('admin.blocks.media.alt')}
            value={item.alt}
            disabled={disabled}
            autoComplete="off"
            error={item.alt.trim() === '' ? t('admin.blocks.error.altRequired') : undefined}
            onChange={(event) => {
              update(index, { alt: event.target.value })
            }}
          />
          <Input
            id={`${idPrefix}-item-${String(index)}-caption`}
            label={t('admin.blocks.media.caption')}
            value={item.caption ?? ''}
            disabled={disabled}
            autoComplete="off"
            onChange={(event) => {
              update(index, { caption: event.target.value })
            }}
          />
        </div>
      ))}
      <div>
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled}
          onClick={() => {
            setPickFor(-1)
          }}
        >
          {t('admin.blocks.media.addImage')}
        </Button>
      </div>
      {pickFor !== null ? (
        <MediaSelectorModal
          currentMediaId={pickFor >= 0 ? (items[pickFor]?.mediaId ?? null) : null}
          onSelect={handleSelect}
          onClose={() => {
            setPickFor(null)
          }}
        />
      ) : null}
    </div>
  )
}

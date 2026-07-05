import { useState } from 'react'
import type { Media } from '@/entities/media'
import { MediaSelectorModal } from '@/features/edit-entity-text-fields/ui/MediaSelectorModal'
import type { ThemeImageRef } from '@/shared/lib/theme-customization'
import { Button } from '@/shared/ui'

interface PickerLabels {
  select: string
  change: string
  remove: string
}

interface ThemeImagePickerProps {
  ariaLabel: string
  currentId: number | undefined
  currentUrl: string | undefined
  labels: PickerLabels
  disabled: boolean
  onSelect: (id: number) => void
  onClear: () => void
}

function ThemeImagePicker({
  ariaLabel,
  currentId,
  currentUrl,
  labels,
  disabled,
  onSelect,
  onClear,
}: ThemeImagePickerProps) {
  const [open, setOpen] = useState(false)

  return (
    <span className="flex items-center gap-inline-sm">
      {currentUrl !== undefined ? (
        // Decorative thumbnail; the picker button carries the accessible name.
        <img
          src={currentUrl}
          alt=""
          className="h-8 w-12 rounded-sm border border-border object-cover"
        />
      ) : (
        <span
          className="flex h-8 w-12 items-center justify-center rounded-sm border border-dashed border-border text-caption text-text-muted"
          aria-hidden="true"
        >
          —
        </span>
      )}
      <Button
        type="button"
        size="sm"
        variant="ghost"
        disabled={disabled}
        aria-label={ariaLabel}
        onClick={() => {
          setOpen(true)
        }}
      >
        {currentId !== undefined ? labels.change : labels.select}
      </Button>
      {currentId !== undefined ? (
        <Button type="button" size="sm" variant="ghost" disabled={disabled} onClick={onClear}>
          {labels.remove}
        </Button>
      ) : null}
      {open ? (
        <MediaSelectorModal
          currentMediaId={currentId !== undefined ? String(currentId) : null}
          onSelect={(media: Media) => {
            onSelect(media.id)
            setOpen(false)
          }}
          onClose={() => {
            setOpen(false)
          }}
        />
      ) : null}
    </span>
  )
}

export interface ThemeImageFieldProps {
  label: string
  lightLabel: string
  darkLabel: string
  pickerLabels: PickerLabels
  value: ThemeImageRef | undefined
  /** Resolve the stored media id of a mode to a thumbnail URL (from the media list). */
  urlFor: (mode: 'light' | 'dark') => string | undefined
  disabled: boolean
  onChange: (mode: 'light' | 'dark', id: number | undefined) => void
}

/**
 * A theme image slot (logo / hero / background) with a light + dark media picker,
 * reusing the media library's {@see MediaSelectorModal}. Stores the media **id**;
 * the public endpoint resolves it to a URL (#372).
 */
export function ThemeImageField({
  label,
  lightLabel,
  darkLabel,
  pickerLabels,
  value,
  urlFor,
  disabled,
  onChange,
}: ThemeImageFieldProps) {
  const idOf = (mode: 'light' | 'dark'): number | undefined =>
    typeof value?.[mode] === 'number' ? value[mode] : undefined

  return (
    <div className="flex items-center justify-between gap-inline-md">
      <span className="font-chrome text-caption font-semibold text-text-primary">{label}</span>
      <span className="flex items-center gap-inline-md">
        <ThemeImagePicker
          ariaLabel={`${label} (${lightLabel})`}
          currentId={idOf('light')}
          currentUrl={urlFor('light')}
          labels={pickerLabels}
          disabled={disabled}
          onSelect={(id) => {
            onChange('light', id)
          }}
          onClear={() => {
            onChange('light', undefined)
          }}
        />
        <ThemeImagePicker
          ariaLabel={`${label} (${darkLabel})`}
          currentId={idOf('dark')}
          currentUrl={urlFor('dark')}
          labels={pickerLabels}
          disabled={disabled}
          onSelect={(id) => {
            onChange('dark', id)
          }}
          onClear={() => {
            onChange('dark', undefined)
          }}
        />
      </span>
    </div>
  )
}

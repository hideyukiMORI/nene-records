import { useTranslation } from '@/shared/i18n'
import { Button, Input } from '@/shared/ui'
import { IconChevronDown, IconChevronUp, IconX } from '@/shared/ui/icons/Icons'
import { type SeriesPoint } from '@/shared/lib/blocks-document'
import { moveItem } from '@/shared/lib/move-item'
import { FieldError } from './FieldError'
import { RepeaterIconButton } from './RepeaterIconButton'

interface SeriesFieldProps {
  idPrefix: string
  series: SeriesPoint[]
  disabled: boolean
  error?: string | undefined
  onChange: (series: SeriesPoint[]) => void
}

/** Repeater of chart data points — label + numeric value (#486 S5). */
export function SeriesField({ idPrefix, series, disabled, error, onChange }: SeriesFieldProps) {
  const { t } = useTranslation()

  const update = (index: number, patch: Partial<SeriesPoint>) => {
    onChange(series.map((point, i) => (i === index ? { ...point, ...patch } : point)))
  }
  const move = (index: number, direction: -1 | 1) => {
    const next = moveItem(series, index, direction)
    if (next !== null) {
      onChange(next)
    }
  }
  const remove = (index: number) => {
    onChange(series.filter((_, i) => i !== index))
  }

  return (
    <div className="flex flex-col gap-stack-xs">
      <span className="font-sans text-caption font-medium text-text-primary">
        {t('admin.blocks.field.series')}
      </span>
      {error !== undefined ? <FieldError>{error}</FieldError> : null}
      {series.map((point, index) => (
        <div key={index} className="flex items-end gap-inline-sm">
          <div className="flex-1">
            <Input
              id={`${idPrefix}-series-${String(index)}-label`}
              label={t('admin.blocks.series.label')}
              value={point.label}
              disabled={disabled}
              autoComplete="off"
              error={point.label.trim() === '' ? t('admin.blocks.error.labelRequired') : undefined}
              onChange={(event) => {
                update(index, { label: event.target.value })
              }}
            />
          </div>
          <div className="w-24">
            <Input
              id={`${idPrefix}-series-${String(index)}-value`}
              label={t('admin.blocks.series.value')}
              type="number"
              value={String(point.value)}
              disabled={disabled}
              onChange={(event) => {
                const next = Number(event.target.value)
                update(index, { value: Number.isFinite(next) ? next : 0 })
              }}
            />
          </div>
          <RepeaterIconButton
            title={t('admin.blocks.moveUp')}
            disabled={disabled || index === 0}
            onClick={() => {
              move(index, -1)
            }}
          >
            <IconChevronUp size={15} />
          </RepeaterIconButton>
          <RepeaterIconButton
            title={t('admin.blocks.moveDown')}
            disabled={disabled || index === series.length - 1}
            onClick={() => {
              move(index, 1)
            }}
          >
            <IconChevronDown size={15} />
          </RepeaterIconButton>
          <RepeaterIconButton
            danger
            title={t('common.actions.delete')}
            disabled={disabled}
            onClick={() => {
              remove(index)
            }}
          >
            <IconX size={15} />
          </RepeaterIconButton>
        </div>
      ))}
      <div>
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled}
          onClick={() => {
            onChange([...series, { label: '', value: 0 }])
          }}
        >
          {t('admin.blocks.series.add')}
        </Button>
      </div>
    </div>
  )
}

import type { WidgetType } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { Card, Stack, Text } from '@/shared/ui'
import { WIDGET_CATALOG } from '../widget-catalog'
import { setDragPayload, clearDragPayload } from './widget-dnd'

export interface WidgetPaletteProps {
  onAdd: (type: WidgetType) => void
}

/** Draggable widget chips. Drag onto a region to place; click to append. */
export function WidgetPalette({ onAdd }: WidgetPaletteProps) {
  const { t } = useTranslation()
  return (
    <Card className="flex flex-col gap-stack-sm">
      <Text as="h2" variant="heading-sm">
        {t('admin.layout.palette')}
      </Text>
      <Text as="span" muted variant="caption">
        {t('admin.layout.paletteHint')}
      </Text>
      <ul className="flex flex-col gap-stack-xs">
        {WIDGET_CATALOG.map((c) => (
          <li key={c.type}>
            <button
              type="button"
              draggable
              onDragStart={(e) => {
                setDragPayload({ kind: 'new', type: c.type })
                e.dataTransfer.effectAllowed = 'copy'
              }}
              onDragEnd={() => {
                clearDragPayload()
              }}
              onClick={() => {
                onAdd(c.type)
              }}
              className="flex w-full cursor-grab items-center justify-between gap-inline-sm rounded-md border border-border bg-surface-raised px-inline-md py-stack-sm text-left hover:bg-surface-overlay active:cursor-grabbing"
            >
              <Stack gap="xs">
                <Text as="span" variant="body">
                  {t(c.labelKey)}
                </Text>
                <Text as="span" muted variant="caption">
                  {t(c.descKey)}
                </Text>
              </Stack>
              <span aria-hidden className="text-text-muted">
                ⠿
              </span>
            </button>
          </li>
        ))}
      </ul>
    </Card>
  )
}

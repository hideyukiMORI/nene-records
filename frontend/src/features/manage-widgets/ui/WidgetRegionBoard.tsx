import { useState, type DragEvent, type ReactNode } from 'react'
import type { Menu } from '@/entities/menu'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { bodyColumns, type LayoutConfig } from '@/shared/lib/layout-config'
import type { WidgetRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Stack, Text } from '@/shared/ui'
import { WIDGET_CATALOG_BY_TYPE } from '../widget-catalog'
import {
  clearDragPayload,
  computeDropIndex,
  getDragPayload,
  setDragPayload,
  type DragPayload,
} from './widget-dnd'

export interface WidgetRegionBoardProps {
  widgets: Widget[]
  menus: Menu[]
  selectedId: number | null
  cfg: LayoutConfig
  dnd: boolean
  onAddToRegion: (region: WidgetRegion) => void
  onSelect: (id: number) => void
  onRemove: (id: number) => void
  onDrop: (region: WidgetRegion, index: number, payload: DragPayload) => void
}

const REGION_FLOW: Record<WidgetRegion, 'row' | 'col'> = {
  header: 'row',
  sidebar: 'col',
  aside: 'col',
  footer: 'col',
}

export function WidgetRegionBoard({
  widgets,
  menus,
  selectedId,
  cfg,
  dnd,
  onAddToRegion,
  onSelect,
  onRemove,
  onDrop,
}: WidgetRegionBoardProps) {
  const { t } = useTranslation()
  const [hover, setHover] = useState<{ region: WidgetRegion; idx: number } | null>(null)

  const byRegion = (region: WidgetRegion): Widget[] =>
    widgets
      .filter((w) => w.region === region)
      .sort((a, b) => a.displayOrder - b.displayOrder || a.id - b.id)

  const subLabel = (w: Widget): string => {
    if (w.widgetType === 'menu') {
      const m = menus.find((x) => x.id === w.settings['menuId'])
      return `${t('admin.widgets.type.menu')} · ${m ? m.name : t('admin.widgets.menuPlaceholder')}`
    }
    return t(WIDGET_CATALOG_BY_TYPE[w.widgetType].labelKey)
  }

  const renderCard = (w: Widget) => (
    <Card
      as="li"
      key={w.id}
      padding="row"
      data-wcard={w.id}
      draggable={dnd}
      onDragStart={
        dnd
          ? (e: DragEvent<HTMLElement>) => {
              setDragPayload({ kind: 'move', id: w.id })
              e.dataTransfer.effectAllowed = 'move'
            }
          : undefined
      }
      onDragEnd={
        dnd
          ? () => {
              clearDragPayload()
            }
          : undefined
      }
      onClick={() => {
        onSelect(w.id)
      }}
      className={`flex cursor-pointer items-center justify-between gap-inline-sm ${
        selectedId === w.id ? 'ring-2 ring-accent' : ''
      }`}
    >
      <Stack gap="xs">
        <Text as="span" variant="body">
          {w.title && w.title.trim() !== ''
            ? w.title
            : t(WIDGET_CATALOG_BY_TYPE[w.widgetType].labelKey)}
        </Text>
        <Text as="span" muted variant="caption">
          {subLabel(w)}
        </Text>
      </Stack>
      <Button
        variant="danger"
        size="sm"
        onClick={(e) => {
          e.stopPropagation()
          onRemove(w.id)
        }}
      >
        {t('common.actions.delete')}
      </Button>
    </Card>
  )

  const renderRegion = (region: WidgetRegion) => {
    const list = byRegion(region)
    const flow = REGION_FLOW[region]
    const cond =
      region === 'sidebar' || region === 'aside' ? t(`admin.layout.cond.${region}`) : null

    const handleDragOver = (e: DragEvent<HTMLDivElement>) => {
      if (!dnd || getDragPayload() === null) return
      e.preventDefault()
      const idx = computeDropIndex(e.currentTarget, e.clientX, e.clientY, flow)
      setHover({ region, idx })
    }
    const handleDrop = (e: DragEvent<HTMLDivElement>) => {
      const payload = getDragPayload()
      if (payload === null) return
      e.preventDefault()
      const idx = computeDropIndex(e.currentTarget, e.clientX, e.clientY, flow)
      onDrop(region, idx, payload)
      setHover(null)
    }
    const dropLine = (i: number) =>
      hover !== null && hover.region === region && hover.idx === i ? (
        <li key={`dl-${String(i)}`} aria-hidden className="h-0.5 rounded bg-accent" />
      ) : null

    const items: ReactNode[] = []
    list.forEach((w, i) => {
      const line = dropLine(i)
      if (line !== null) items.push(line)
      items.push(renderCard(w))
    })
    const endLine = dropLine(list.length)
    if (endLine !== null) items.push(endLine)

    return (
      <Card className="flex flex-col gap-stack-sm">
        <div className="flex items-center justify-between gap-inline-sm">
          <span className="flex flex-col">
            <Text as="span" variant="heading-sm">
              {t(`admin.region.${region}`)}
            </Text>
            {cond !== null ? (
              <Text as="span" muted variant="caption">
                {cond}
              </Text>
            ) : null}
          </span>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              onAddToRegion(region)
            }}
          >
            {t('admin.widgets.board.addHere')}
          </Button>
        </div>
        <div
          onDragOver={handleDragOver}
          onDragLeave={() => {
            setHover((h) => (h !== null && h.region === region ? null : h))
          }}
          onDrop={handleDrop}
        >
          {list.length === 0 ? (
            <div className="flex min-h-16 flex-col items-center justify-center gap-stack-xs rounded-md border border-dashed border-border px-inline-md py-stack-md text-center">
              <Text muted variant="caption">
                {dnd ? t('admin.layout.dropHere') : t('admin.widgets.board.empty')}
              </Text>
            </div>
          ) : (
            <ul
              className={
                flow === 'row'
                  ? 'flex flex-row flex-wrap gap-inline-sm'
                  : 'flex flex-col gap-stack-xs'
              }
            >
              {items}
            </ul>
          )}
        </div>
      </Card>
    )
  }

  const cols = bodyColumns(cfg)

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        {t('admin.widgets.board.title')}
      </Text>
      <Text muted variant="caption">
        {t('admin.widgets.board.layoutNote')}
      </Text>

      {renderRegion('header')}

      <div
        className="grid gap-stack-md"
        style={{
          gridTemplateColumns: cols.map((c) => (c === 'main' ? '1.6fr' : '1fr')).join(' '),
        }}
      >
        {cols.map((c) =>
          c === 'main' ? (
            <Card key="main" className="flex flex-col gap-stack-sm border-dashed">
              <Text as="span" variant="heading-sm">
                {t('admin.region.main')}
              </Text>
              <div className="flex min-h-16 items-center justify-center rounded-md bg-surface px-inline-md py-stack-md text-center">
                <Text muted variant="caption">
                  {t('admin.widgets.board.mainContent')}
                </Text>
              </div>
            </Card>
          ) : (
            <div key={c}>{renderRegion(c)}</div>
          ),
        )}
      </div>

      {renderRegion('footer')}
    </Stack>
  )
}

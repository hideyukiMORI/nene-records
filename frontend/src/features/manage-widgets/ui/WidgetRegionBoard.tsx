import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { bodyColumns, type LayoutConfig } from '@/shared/lib/layout-config'
import type { WidgetRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Stack, Text } from '@/shared/ui'

export interface WidgetRegionBoardProps {
  widgets: Widget[]
  editId: number | null
  cfg: LayoutConfig
  onAddToRegion: (region: WidgetRegion) => void
  onEdit: (widget: Widget) => void
  onRemove: (id: number) => void
}

/**
 * Visual layout board mirroring the public page: a full-width header bar, the
 * record-detail body columns (driven by the layout config), and a full-width
 * footer bar. Each region (header/sidebar/aside/footer) accepts widgets; `main`
 * is record content.
 */
export function WidgetRegionBoard({
  widgets,
  editId,
  cfg,
  onAddToRegion,
  onEdit,
  onRemove,
}: WidgetRegionBoardProps) {
  const { t } = useTranslation()

  const byRegion = (region: WidgetRegion): Widget[] =>
    widgets
      .filter((widget) => widget.region === region)
      .sort((a, b) => a.displayOrder - b.displayOrder || a.id - b.id)

  const renderCard = (widget: Widget) => (
    <Card
      as="li"
      key={widget.id}
      padding="row"
      className={`flex items-center justify-between gap-inline-sm ${
        editId === widget.id ? 'ring-2 ring-accent' : ''
      }`}
    >
      <Stack gap="xs">
        <Text as="span" variant="heading-sm">
          {widget.title ?? t(`admin.widgets.type.${widget.widgetType}`)}
        </Text>
        <Text as="span" muted variant="caption">
          {t(`admin.widgets.type.${widget.widgetType}`)}
        </Text>
      </Stack>
      <div className="flex items-center gap-inline-sm">
        <Button
          variant="secondary"
          size="sm"
          onClick={() => {
            onEdit(widget)
          }}
        >
          {t('common.actions.edit')}
        </Button>
        <Button
          variant="danger"
          size="sm"
          onClick={() => {
            onRemove(widget.id)
          }}
        >
          {t('common.actions.delete')}
        </Button>
      </div>
    </Card>
  )

  const renderRegion = (region: WidgetRegion) => {
    const regionWidgets = byRegion(region)
    return (
      <Card className="flex flex-col gap-stack-sm">
        <div className="flex items-center justify-between gap-inline-sm">
          <Text as="span" variant="heading-sm">
            {t(`admin.region.${region}`)}
          </Text>
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
        {regionWidgets.length === 0 ? (
          <div className="flex min-h-16 items-center justify-center rounded-md border border-dashed border-border px-inline-md py-stack-md text-center">
            <Text muted variant="caption">
              {t('admin.widgets.board.empty')}
            </Text>
          </div>
        ) : (
          <ul className="flex flex-col gap-stack-xs">{regionWidgets.map(renderCard)}</ul>
        )}
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

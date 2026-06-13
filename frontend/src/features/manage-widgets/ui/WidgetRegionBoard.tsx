import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import type { ContentRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Stack, Text } from '@/shared/ui'

export interface WidgetRegionBoardProps {
  widgets: Widget[]
  editId: number | null
  onAddToRegion: (region: ContentRegion) => void
  onEdit: (widget: Widget) => void
  onRemove: (id: number) => void
}

const SECONDARY_REGIONS: readonly ContentRegion[] = ['sidebar', 'aside']

// Mirrors the public three-column proportion (main 2 / sidebar 1 / aside 1).
// Kept in a constant so the arbitrary Tailwind value is not a className literal.
const BOARD_GRID_CLASS = 'grid grid-cols-1 gap-stack-md lg:grid-cols-[2fr_1fr_1fr]'

/**
 * Visual layout board: shows widgets grouped into the regions a public page
 * renders (main = content, sidebar/aside = widget columns), in the same 2/1/1
 * proportion as the public two/three-column layouts.
 */
export function WidgetRegionBoard({
  widgets,
  editId,
  onAddToRegion,
  onEdit,
  onRemove,
}: WidgetRegionBoardProps) {
  const { t } = useTranslation()

  const byRegion = (region: ContentRegion): Widget[] =>
    widgets
      .filter((widget) => widget.region === region)
      .sort((a, b) => a.displayOrder - b.displayOrder || a.id - b.id)

  const mainWidgets = byRegion('main')

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

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        {t('admin.widgets.board.title')}
      </Text>
      <Text muted variant="caption">
        {t('admin.widgets.board.layoutNote')}
      </Text>

      <div className={BOARD_GRID_CLASS}>
        {/* main = record content, not a widget target */}
        <Card className="flex flex-col gap-stack-sm border-dashed">
          <Text as="span" variant="heading-sm">
            {t('admin.region.main')}
          </Text>
          <div className="flex min-h-24 items-center justify-center rounded-md bg-surface px-inline-md py-stack-lg text-center">
            <Text muted variant="caption">
              {t('admin.widgets.board.mainContent')}
            </Text>
          </div>
          {mainWidgets.length > 0 ? (
            <Stack gap="xs">
              <Text muted variant="caption">
                {t('admin.widgets.board.mainNote')}
              </Text>
              <ul className="flex flex-col gap-stack-xs">{mainWidgets.map(renderCard)}</ul>
            </Stack>
          ) : null}
        </Card>

        {SECONDARY_REGIONS.map((region) => {
          const regionWidgets = byRegion(region)
          return (
            <Card key={region} className="flex flex-col gap-stack-sm">
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
                <div className="flex min-h-24 items-center justify-center rounded-md border border-dashed border-border px-inline-md py-stack-lg text-center">
                  <Text muted variant="caption">
                    {t('admin.widgets.board.empty')}
                  </Text>
                </div>
              ) : (
                <ul className="flex flex-col gap-stack-xs">{regionWidgets.map(renderCard)}</ul>
              )}
            </Card>
          )
        })}
      </div>
    </Stack>
  )
}

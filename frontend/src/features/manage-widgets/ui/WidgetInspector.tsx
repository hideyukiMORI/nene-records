import type { EntityType } from '@/entities/entity-type'
import type { Menu } from '@/entities/menu'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { WIDGET_REGIONS, type WidgetRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Input, Select, Text } from '@/shared/ui'
import { WIDGET_CATALOG_BY_TYPE, type SettingDescriptor } from '../widget-catalog'

export interface WidgetInspectorProps {
  widget: Widget | null
  menus: Menu[]
  entityTypes: EntityType[]
  onTitle: (id: number, title: string) => void
  onSettings: (id: number, patch: Record<string, unknown>) => void
  onChangeRegion: (id: number, region: WidgetRegion) => void
  onRemove: (id: number) => void
}

export function WidgetInspector({
  widget,
  menus,
  entityTypes,
  onTitle,
  onSettings,
  onChangeRegion,
  onRemove,
}: WidgetInspectorProps) {
  const { t } = useTranslation()

  if (widget === null) {
    return (
      <Card className="flex flex-col gap-stack-xs">
        <Text as="h2" variant="heading-sm">
          {t('admin.layout.inspector')}
        </Text>
        <Text muted variant="caption">
          {t('admin.layout.inspectorEmpty')}
        </Text>
      </Card>
    )
  }

  const entry = WIDGET_CATALOG_BY_TYPE[widget.widgetType]

  const renderField = (s: SettingDescriptor) => {
    const value = widget.settings[s.key]
    if (s.editor === 'menu') {
      return (
        <Select
          key={s.key}
          id={`insp-${s.key}`}
          label={t(s.labelKey)}
          value={typeof value === 'number' ? String(value) : ''}
          onChange={(e) => {
            onSettings(widget.id, { menuId: e.target.value === '' ? null : Number(e.target.value) })
          }}
        >
          <option value="">{t('admin.widgets.menuPlaceholder')}</option>
          {menus.map((m) => (
            <option key={m.id} value={String(m.id)}>
              {m.name}
            </option>
          ))}
        </Select>
      )
    }
    if (s.editor === 'enum' && s.key === 'entityTypeSlug') {
      return (
        <Select
          key={s.key}
          id={`insp-${s.key}`}
          label={t(s.labelKey)}
          value={typeof value === 'string' ? value : ''}
          onChange={(e) => {
            onSettings(widget.id, { entityTypeSlug: e.target.value })
          }}
        >
          <option value="">{t('admin.widgets.entityTypePlaceholder')}</option>
          {entityTypes.map((type) => (
            <option key={String(type.id)} value={type.slug}>
              {type.name}
            </option>
          ))}
        </Select>
      )
    }
    if (s.editor === 'int') {
      return (
        <Input
          key={s.key}
          id={`insp-${s.key}`}
          type="number"
          label={t(s.labelKey)}
          value={String(typeof value === 'number' ? value : (s.def ?? 1))}
          onChange={(e) => {
            onSettings(widget.id, { [s.key]: Number(e.target.value) || 1 })
          }}
        />
      )
    }
    return (
      <Input
        key={s.key}
        id={`insp-${s.key}`}
        label={t(s.labelKey)}
        value={typeof value === 'string' ? value : ''}
        onChange={(e) => {
          onSettings(widget.id, { [s.key]: e.target.value })
        }}
      />
    )
  }

  return (
    <Card className="flex flex-col gap-stack-sm">
      <Text as="h2" variant="heading-sm">
        {t(entry.labelKey)}
      </Text>
      <Input
        id="insp-title"
        label={t('admin.widgets.titleLabel')}
        value={widget.title ?? ''}
        onChange={(e) => {
          onTitle(widget.id, e.target.value)
        }}
      />
      {entry.settings.map(renderField)}

      <div>
        <Text as="span" muted variant="caption">
          {t('admin.region.label')}
        </Text>
        <div className="mt-1 flex flex-wrap gap-inline-xs">
          {WIDGET_REGIONS.map((r) => (
            <Button
              key={r}
              size="sm"
              variant={widget.region === r ? 'primary' : 'secondary'}
              onClick={() => {
                onChangeRegion(widget.id, r)
              }}
            >
              {t(`admin.region.${r}`)}
            </Button>
          ))}
        </div>
      </div>

      <div>
        <Button
          variant="danger"
          size="sm"
          onClick={() => {
            onRemove(widget.id)
          }}
        >
          {t('admin.widgets.board.deleteWidget')}
        </Button>
      </div>
    </Card>
  )
}

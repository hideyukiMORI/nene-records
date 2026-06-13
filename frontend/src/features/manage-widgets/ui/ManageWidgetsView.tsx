import type { EntityType } from '@/entities/entity-type'
import type { Menu } from '@/entities/menu'
import type { Widget, WidgetType } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { WIDGET_REGIONS, type WidgetRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Input, Select, Stack, Text } from '@/shared/ui'
import type { WidgetFormState } from '../hooks/use-manage-widgets-page'
import { WidgetRegionBoard } from './WidgetRegionBoard'

const WIDGET_TYPES: readonly WidgetType[] = [
  'recent-posts',
  'menu',
  'toc',
  'search',
  'tag-cloud',
  'popular-posts',
  'calendar',
]

export interface ManageWidgetsViewProps {
  widgets: Widget[]
  entityTypes: EntityType[]
  menus: Menu[]
  form: WidgetFormState
  editId: number | null
  isSubmitting: boolean
  setField: <K extends keyof WidgetFormState>(key: K, value: WidgetFormState[K]) => void
  resetForm: () => void
  addToRegion: (region: WidgetRegion) => void
  editWidget: (widget: Widget) => void
  submit: () => Promise<void>
  remove: (id: number) => Promise<void>
}

export function ManageWidgetsView({
  widgets,
  entityTypes,
  menus,
  form,
  editId,
  isSubmitting,
  setField,
  resetForm,
  addToRegion,
  editWidget,
  submit,
  remove,
}: ManageWidgetsViewProps) {
  const { t } = useTranslation()

  return (
    <Stack gap="lg">
      <Card
        as="form"
        onSubmit={(e) => {
          e.preventDefault()
          void submit()
        }}
      >
        <Stack gap="md">
          <Text as="h2" variant="heading-sm">
            {editId !== null ? t('admin.widgets.editTitle') : t('admin.widgets.createTitle')}
          </Text>
          <Select
            id="widget-type"
            label={t('admin.widgets.typeLabel')}
            value={form.widgetType}
            onChange={(e) => {
              setField('widgetType', e.target.value as WidgetType)
            }}
          >
            {WIDGET_TYPES.map((type) => (
              <option key={type} value={type}>
                {t(`admin.widgets.type.${type}`)}
              </option>
            ))}
          </Select>
          <Select
            id="widget-region"
            label={t('admin.region.label')}
            value={form.region}
            onChange={(e) => {
              setField('region', e.target.value as WidgetRegion)
            }}
          >
            {WIDGET_REGIONS.map((region) => (
              <option key={region} value={region}>
                {t(`admin.region.${region}`)}
              </option>
            ))}
          </Select>
          <Input
            id="widget-title"
            label={t('admin.widgets.titleLabel')}
            value={form.title}
            onChange={(e) => {
              setField('title', e.target.value)
            }}
          />
          {form.widgetType === 'recent-posts' ? (
            <>
              <Text muted variant="caption">
                {t('admin.widgets.recentPostsSettings')}
              </Text>
              <Select
                id="widget-entity-type"
                label={t('admin.widgets.entityTypeLabel')}
                value={form.entityTypeSlug}
                onChange={(e) => {
                  setField('entityTypeSlug', e.target.value)
                }}
              >
                <option value="">{t('admin.widgets.entityTypePlaceholder')}</option>
                {entityTypes.map((type) => (
                  <option key={String(type.id)} value={type.slug}>
                    {type.name}
                  </option>
                ))}
              </Select>
              <Input
                id="widget-limit"
                type="number"
                label={t('admin.widgets.limitLabel')}
                value={String(form.limit)}
                onChange={(e) => {
                  setField('limit', Number(e.target.value) || 1)
                }}
              />
            </>
          ) : form.widgetType === 'menu' ? (
            <>
              <Text muted variant="caption">
                {t('admin.widgets.menuSettings')}
              </Text>
              <Select
                id="widget-menu"
                label={t('admin.widgets.menuLabel')}
                value={form.menuId === null ? '' : String(form.menuId)}
                onChange={(e) => {
                  setField('menuId', e.target.value === '' ? null : Number(e.target.value))
                }}
              >
                <option value="">{t('admin.widgets.menuPlaceholder')}</option>
                {menus.map((menu) => (
                  <option key={menu.id} value={String(menu.id)}>
                    {menu.name}
                  </option>
                ))}
              </Select>
            </>
          ) : form.widgetType === 'toc' ? (
            <Text muted variant="caption">
              {t('admin.widgets.tocSettings')}
            </Text>
          ) : form.widgetType === 'search' ? (
            <>
              <Text muted variant="caption">
                {t('admin.widgets.searchSettings')}
              </Text>
              <Input
                id="widget-search-placeholder"
                label={t('admin.widgets.searchPlaceholderLabel')}
                value={form.searchPlaceholder}
                onChange={(e) => {
                  setField('searchPlaceholder', e.target.value)
                }}
              />
            </>
          ) : form.widgetType === 'tag-cloud' ? (
            <Text muted variant="caption">
              {t('admin.widgets.tagCloudSettings')}
            </Text>
          ) : form.widgetType === 'popular-posts' ? (
            <>
              <Text muted variant="caption">
                {t('admin.widgets.popularPostsSettings')}
              </Text>
              <Input
                id="widget-popular-limit"
                type="number"
                label={t('admin.widgets.limitLabel')}
                value={String(form.limit)}
                onChange={(e) => {
                  setField('limit', Number(e.target.value) || 1)
                }}
              />
            </>
          ) : (
            <Text muted variant="caption">
              {t('admin.widgets.calendarSettings')}
            </Text>
          )}
          <div className="flex items-center gap-inline-sm">
            <Button type="submit" disabled={isSubmitting}>
              {editId !== null ? t('common.actions.save') : t('admin.widgets.add')}
            </Button>
            {editId !== null ? (
              <Button type="button" variant="secondary" onClick={resetForm}>
                {t('common.actions.cancel')}
              </Button>
            ) : null}
          </div>
        </Stack>
      </Card>

      <WidgetRegionBoard
        widgets={widgets}
        editId={editId}
        onAddToRegion={(region) => {
          addToRegion(region)
        }}
        onEdit={editWidget}
        onRemove={(id) => {
          void remove(id)
        }}
      />
    </Stack>
  )
}

import type { EntityType } from '@/entities/entity-type'
import { NAV_LOCATIONS, type NavLocation } from '@/entities/navigation-item'
import type { Widget, WidgetType } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import type { ContentRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Input, Select, Stack, Text } from '@/shared/ui'
import type { WidgetFormState } from '../hooks/use-manage-widgets-page'

const WIDGET_TYPES: readonly WidgetType[] = [
  'recent-posts',
  'menu',
  'toc',
  'search',
  'tag-cloud',
  'popular-posts',
]

export interface ManageWidgetsViewProps {
  widgets: Widget[]
  entityTypes: EntityType[]
  form: WidgetFormState
  editId: number | null
  isSubmitting: boolean
  setField: <K extends keyof WidgetFormState>(key: K, value: WidgetFormState[K]) => void
  resetForm: () => void
  editWidget: (widget: Widget) => void
  submit: () => Promise<void>
  remove: (id: number) => Promise<void>
}

export function ManageWidgetsView({
  widgets,
  entityTypes,
  form,
  editId,
  isSubmitting,
  setField,
  resetForm,
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
              setField('region', e.target.value as ContentRegion)
            }}
          >
            <option value="sidebar">{t('admin.region.sidebar')}</option>
            <option value="aside">{t('admin.region.aside')}</option>
            <option value="main">{t('admin.region.main')}</option>
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
                id="widget-menu-location"
                label={t('admin.navigation.location')}
                value={form.menuLocation}
                onChange={(e) => {
                  setField('menuLocation', e.target.value as NavLocation)
                }}
              >
                {NAV_LOCATIONS.map((location) => (
                  <option key={location} value={location}>
                    {t(`admin.navigation.location.${location}`)}
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
          ) : (
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

      <Stack gap="sm">
        <Text as="h2" variant="heading-sm">
          {t('admin.widgets.listTitle')}
        </Text>
        {widgets.length === 0 ? (
          <Text muted>{t('admin.widgets.empty')}</Text>
        ) : (
          <ul className="flex flex-col gap-stack-xs">
            {widgets.map((widget) => (
              <Card
                as="li"
                key={widget.id}
                padding="row"
                className="flex items-center justify-between gap-inline-md"
              >
                <Stack gap="xs">
                  <Text as="span" variant="heading-sm">
                    {widget.title ?? widget.widgetType}
                  </Text>
                  <Text as="span" muted variant="caption">
                    {t(`admin.region.${widget.region}`)} ·{' '}
                    {t(`admin.widgets.type.${widget.widgetType}`)}
                  </Text>
                </Stack>
                <div className="flex items-center gap-inline-sm">
                  <Button
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      editWidget(widget)
                    }}
                  >
                    {t('common.actions.edit')}
                  </Button>
                  <Button
                    variant="danger"
                    size="sm"
                    onClick={() => {
                      void remove(widget.id)
                    }}
                  >
                    {t('common.actions.delete')}
                  </Button>
                </div>
              </Card>
            ))}
          </ul>
        )}
      </Stack>
    </Stack>
  )
}

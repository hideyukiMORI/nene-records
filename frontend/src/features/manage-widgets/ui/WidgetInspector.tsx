import { useState } from 'react'
import type { EntityType } from '@/entities/entity-type'
import type { Menu } from '@/entities/menu'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { WIDGET_REGIONS, type WidgetRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Input, Select, Text } from '@/shared/ui'
import { WIDGET_CATALOG_BY_TYPE, type SettingDescriptor } from '../widget-catalog'

/**
 * Free-text / numeric inspector field with a local draft so edits commit on blur
 * / Enter instead of firing a PUT on every keystroke (which raced with the
 * refetch and corrupted the input). Reset via `key={widget.id-field}`.
 */
function DraftInput({
  id,
  label,
  type,
  value,
  onCommit,
}: {
  id: string
  label: string
  type?: 'text' | 'number'
  value: string
  onCommit: (raw: string) => void
}) {
  const [draft, setDraft] = useState(value)
  return (
    <Input
      id={id}
      label={label}
      type={type ?? 'text'}
      value={draft}
      onChange={(e) => {
        setDraft(e.target.value)
      }}
      onBlur={() => {
        if (draft !== value) {
          onCommit(draft)
        }
      }}
      onKeyDown={(e) => {
        if (e.key === 'Enter') {
          e.currentTarget.blur()
        }
      }}
    />
  )
}

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
    if (s.editor === 'enum' && s.key === 'layout') {
      // Footer link flow (#782): only meaningful for footer columns, so the
      // control is hidden in other regions (the setting is simply ignored there).
      if (widget.region !== 'footer') {
        return null
      }
      return (
        <Select
          key={s.key}
          id={`insp-${s.key}`}
          label={t(s.labelKey)}
          value={typeof value === 'string' ? value : 'stack'}
          onChange={(e) => {
            onSettings(widget.id, { layout: e.target.value })
          }}
        >
          <option value="stack">{t('admin.widgets.menuLayout.stack')}</option>
          <option value="inline">{t('admin.widgets.menuLayout.inline')}</option>
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
    if (s.editor === 'bool') {
      return (
        <label key={s.key} htmlFor={`insp-${s.key}`} className="flex items-center gap-inline-sm">
          <input
            id={`insp-${s.key}`}
            type="checkbox"
            checked={value === true}
            onChange={(e) => {
              onSettings(widget.id, { [s.key]: e.target.checked })
            }}
          />
          <Text as="span" variant="body">
            {t(s.labelKey)}
          </Text>
        </label>
      )
    }
    if (s.editor === 'int') {
      const fallback = s.def ?? 1
      return (
        <DraftInput
          key={`${String(widget.id)}-${s.key}`}
          id={`insp-${s.key}`}
          type="number"
          label={t(s.labelKey)}
          value={String(typeof value === 'number' ? value : fallback)}
          onCommit={(raw) => {
            // Parse without the `|| 1` trap that forced 0 / empty to 1.
            const parsed = Number.parseInt(raw, 10)
            onSettings(widget.id, { [s.key]: Number.isFinite(parsed) ? parsed : fallback })
          }}
        />
      )
    }
    return (
      <DraftInput
        key={`${String(widget.id)}-${s.key}`}
        id={`insp-${s.key}`}
        label={t(s.labelKey)}
        value={typeof value === 'string' ? value : ''}
        onCommit={(raw) => {
          onSettings(widget.id, { [s.key]: raw })
        }}
      />
    )
  }

  return (
    <Card className="flex flex-col gap-stack-sm">
      <Text as="h2" variant="heading-sm">
        {t(entry.labelKey)}
      </Text>
      <DraftInput
        key={`${String(widget.id)}-title`}
        id="insp-title"
        label={t('admin.widgets.titleLabel')}
        value={widget.title ?? ''}
        onCommit={(raw) => {
          onTitle(widget.id, raw)
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

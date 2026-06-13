import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import {
  activeSideRegions,
  loadLayoutConfig,
  saveLayoutConfig,
  type LayoutConfig,
} from '@/shared/lib/layout-config'
import type { WidgetRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Stack, Text } from '@/shared/ui'
import type { useManageWidgetsPage } from '../hooks/use-manage-widgets-page'
import { LayoutConfigBar } from './LayoutConfigBar'
import { LayoutPreview, type PreviewPage } from './LayoutPreview'
import { WidgetInspector } from './WidgetInspector'
import { WidgetPalette } from './WidgetPalette'
import { WidgetRegionBoard } from './WidgetRegionBoard'

// 3-pane workspace columns: palette | board | inspector.
const WORKSPACE_CLASS =
  'grid grid-cols-1 gap-stack-lg lg:grid-cols-[220px_1fr_280px] lg:items-start'
const PREVIEW_CLASS = 'grid grid-cols-1 gap-stack-lg lg:grid-cols-[1fr_1.3fr] lg:items-start'

type LayoutMode = 'dnd' | 'preview'
const MODE_LS_KEY = 'nene_layout_mode'

export interface ManageWidgetsViewProps {
  page: ReturnType<typeof useManageWidgetsPage>
}

export function ManageWidgetsView({ page }: ManageWidgetsViewProps) {
  const { t } = useTranslation()
  const [cfg, setCfgState] = useState<LayoutConfig>(() => loadLayoutConfig())
  const setCfg = (next: LayoutConfig) => {
    setCfgState(next)
    saveLayoutConfig(next)
  }

  const [mode, setMode] = useState<LayoutMode>(() =>
    localStorage.getItem(MODE_LS_KEY) === 'preview' ? 'preview' : 'dnd',
  )
  const changeMode = (next: LayoutMode) => {
    setMode(next)
    localStorage.setItem(MODE_LS_KEY, next)
  }
  const [previewPage, setPreviewPage] = useState<PreviewPage>('record')

  const segBtn = (on: boolean): string =>
    [
      'px-inline-md py-stack-xs text-body',
      on ? 'bg-accent text-text-inverse' : 'bg-surface-raised hover:bg-surface-overlay',
    ].join(' ')

  const active = activeSideRegions(cfg)
  const hiddenRegions = (['sidebar', 'aside'] as const).filter((r) => !active.includes(r))
  const hiddenCount = page.widgets.filter((w) =>
    (hiddenRegions as readonly WidgetRegion[]).includes(w.region),
  ).length

  const selected = page.widgets.find((w) => w.id === page.selectedId) ?? null

  return (
    <Stack gap="lg">
      <div className="flex items-center justify-end">
        <span className="inline-flex overflow-hidden rounded-md border border-border">
          <button
            type="button"
            className={segBtn(mode === 'dnd')}
            onClick={() => {
              changeMode('dnd')
            }}
          >
            {t('admin.layout.modeDnd')}
          </button>
          <button
            type="button"
            className={segBtn(mode === 'preview')}
            onClick={() => {
              changeMode('preview')
            }}
          >
            {t('admin.layout.modePreview')}
          </button>
        </span>
      </div>

      <LayoutConfigBar cfg={cfg} setCfg={setCfg} />
      {hiddenCount > 0 ? (
        <Card className="flex flex-wrap items-center gap-inline-md border-warn">
          <Text as="span" variant="caption">
            {t('admin.layoutCfg.hiddenWarning', {
              count: String(hiddenCount),
              regions: hiddenRegions.map((r) => t(`admin.region.${r}`)).join('・'),
              columns: String(cfg.columns),
            })}
          </Text>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              setCfg({ ...cfg, columns: 3 })
            }}
          >
            {t('admin.layoutCfg.makeThreeCol')}
          </Button>
        </Card>
      ) : null}

      {mode === 'dnd' ? (
        <div className={WORKSPACE_CLASS}>
          <WidgetPalette
            onAdd={(type) => {
              void page.addWidgetAt(type, 'sidebar', null)
            }}
          />

          <WidgetRegionBoard
            widgets={page.widgets}
            menus={page.menus}
            selectedId={page.selectedId}
            cfg={cfg}
            dnd
            onAddToRegion={(region) => {
              void page.addWidgetAt('menu', region, null)
            }}
            onSelect={page.select}
            onRemove={(id) => {
              void page.remove(id)
            }}
            onDrop={(region, index, payload) => {
              if (payload.kind === 'new') {
                void page.addWidgetAt(payload.type, region, index)
              } else {
                void page.moveWidgetAt(payload.id, region, index)
              }
            }}
          />

          <WidgetInspector
            widget={selected}
            menus={page.menus}
            entityTypes={page.entityTypes}
            onTitle={(id, title) => {
              void page.updateTitle(id, title)
            }}
            onSettings={(id, patch) => {
              void page.updateSettings(id, patch)
            }}
            onChangeRegion={(id, region) => {
              void page.moveWidgetAt(id, region, null)
            }}
            onRemove={(id) => {
              void page.remove(id)
            }}
          />
        </div>
      ) : (
        <div className={PREVIEW_CLASS}>
          <WidgetRegionBoard
            widgets={page.widgets}
            menus={page.menus}
            selectedId={page.selectedId}
            cfg={cfg}
            dnd={false}
            onAddToRegion={(region) => {
              void page.addWidgetAt('menu', region, null)
            }}
            onSelect={page.select}
            onRemove={(id) => {
              void page.remove(id)
            }}
            onDrop={() => undefined}
          />
          <Stack gap="sm">
            <div className="flex items-center justify-between gap-inline-md">
              <Text as="span" variant="heading-sm">
                {t('admin.layout.previewTitle')}
              </Text>
              <span className="inline-flex overflow-hidden rounded-md border border-border">
                <button
                  type="button"
                  className={segBtn(previewPage === 'home')}
                  onClick={() => {
                    setPreviewPage('home')
                  }}
                >
                  {t('admin.layout.previewHome')}
                </button>
                <button
                  type="button"
                  className={segBtn(previewPage === 'record')}
                  onClick={() => {
                    setPreviewPage('record')
                  }}
                >
                  {t('admin.layout.previewRecord')}
                </button>
              </span>
            </div>
            <LayoutPreview
              widgets={page.widgets}
              menus={page.menus}
              cfg={cfg}
              page={previewPage}
              selectedId={page.selectedId}
            />
            <Text as="span" muted variant="caption">
              {previewPage === 'home'
                ? t('admin.layout.previewHomeNote')
                : t('admin.layout.previewRecordNote', { columns: String(cfg.columns) })}
            </Text>
          </Stack>
        </div>
      )}
    </Stack>
  )
}

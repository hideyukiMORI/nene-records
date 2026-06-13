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
import { WidgetInspector } from './WidgetInspector'
import { WidgetPalette } from './WidgetPalette'
import { WidgetRegionBoard } from './WidgetRegionBoard'

// 3-pane workspace columns: palette | board | inspector.
const WORKSPACE_CLASS =
  'grid grid-cols-1 gap-stack-lg lg:grid-cols-[220px_1fr_280px] lg:items-start'

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

  const active = activeSideRegions(cfg)
  const hiddenRegions = (['sidebar', 'aside'] as const).filter((r) => !active.includes(r))
  const hiddenCount = page.widgets.filter((w) =>
    (hiddenRegions as readonly WidgetRegion[]).includes(w.region),
  ).length

  const selected = page.widgets.find((w) => w.id === page.selectedId) ?? null

  return (
    <Stack gap="lg">
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
    </Stack>
  )
}

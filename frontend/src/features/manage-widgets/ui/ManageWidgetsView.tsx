import { useEffect, useState } from 'react'
import { useUpdateSetting, useSettingList } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import { setChromeRail } from '@/shared/lib/chrome-rail'
import {
  allActiveSideRegions,
  parseLayoutConfig,
  type LayoutConfig,
  type LayoutPageKey,
  type PageLayout,
} from '@/shared/lib/layout-config'
import type { WidgetRegion } from '@/shared/lib/resolve-layout'
import { Button, Card, Stack, Text } from '@/shared/ui'
import type { useManageWidgetsPage } from '../hooks/use-manage-widgets-page'
import { LayoutConfigBar } from './LayoutConfigBar'
import { LayoutPreview } from './LayoutPreview'
import { WidgetInspector } from './WidgetInspector'
import { WidgetPalette } from './WidgetPalette'
import { WidgetRegionBoard } from './WidgetRegionBoard'

// 3-pane workspace columns: palette | board | inspector.
const WORKSPACE_CLASS =
  'grid grid-cols-1 gap-stack-lg lg:grid-cols-[220px_1fr_280px] lg:items-start'
const PREVIEW_CLASS = 'grid grid-cols-1 gap-stack-lg lg:grid-cols-[1fr_1.3fr] lg:items-start'

type LayoutMode = 'dnd' | 'preview'
const MODE_LS_KEY = 'nene_layout_mode'
const PAGE_LS_KEY = 'nene_layout_page'

export interface ManageWidgetsViewProps {
  page: ReturnType<typeof useManageWidgetsPage>
}

export function ManageWidgetsView({ page }: ManageWidgetsViewProps) {
  const { t } = useTranslation()

  // Layout config is a public setting (`layout_config`) so the top page's
  // columns apply to the public site — not just this admin browser. Edits
  // persist immediately; the draft re-syncs when the stored value loads/changes.
  const settingsQuery = useSettingList()
  const updateSetting = useUpdateSetting()
  const storedLayout = settingsQuery.data?.items.find(
    (item) => item.settingKey === 'layout_config',
  )?.value
  const [cfg, setCfgState] = useState<LayoutConfig>(() => parseLayoutConfig(storedLayout))
  const [syncedLayout, setSyncedLayout] = useState(storedLayout)
  if (storedLayout !== syncedLayout) {
    setSyncedLayout(storedLayout)
    setCfgState(parseLayoutConfig(storedLayout))
  }
  const setCfg = (next: LayoutConfig) => {
    setCfgState(next)
    updateSetting.mutate({ settingKey: 'layout_config', input: { value: JSON.stringify(next) } })
  }

  const [mode, setMode] = useState<LayoutMode>(() =>
    localStorage.getItem(MODE_LS_KEY) === 'preview' ? 'preview' : 'dnd',
  )
  const changeMode = (next: LayoutMode) => {
    setMode(next)
    localStorage.setItem(MODE_LS_KEY, next)
  }

  // The selected page drives *both* which page's columns the config bar edits
  // and which page the live preview renders.
  const [layoutPage, setLayoutPageState] = useState<LayoutPageKey>(() =>
    localStorage.getItem(PAGE_LS_KEY) === 'home' ? 'home' : 'record',
  )
  const setLayoutPage = (next: LayoutPageKey) => {
    setLayoutPageState(next)
    localStorage.setItem(PAGE_LS_KEY, next)
  }

  const pageCfg: PageLayout = cfg[layoutPage]
  const setPageCfg = (next: PageLayout) => {
    setCfg({ ...cfg, [layoutPage]: next })
  }
  const pageLabel =
    layoutPage === 'home' ? t('admin.layout.previewHome') : t('admin.layout.previewRecord')

  // Preview mode collapses the app sidebar into an icon rail (desktop only).
  useEffect(() => {
    setChromeRail(mode === 'preview')
    return () => {
      setChromeRail(false)
    }
  }, [mode])

  const segBtn = (on: boolean): string =>
    [
      'px-inline-md py-stack-xs text-body',
      on ? 'bg-accent text-text-inverse' : 'bg-surface-raised hover:bg-surface-overlay',
    ].join(' ')

  // Widgets in a side region that *no* page's config renders are hidden in public.
  const activeUnion = allActiveSideRegions(cfg)
  const hiddenRegions = (['sidebar', 'aside'] as const).filter((r) => !activeUnion.includes(r))
  const hiddenCount = page.widgets.filter((w) =>
    (hiddenRegions as readonly WidgetRegion[]).includes(w.region),
  ).length

  const mainLabel =
    pageCfg.columns >= 3 && pageCfg.mainPos === 'center'
      ? t('admin.layoutCfg.posCenter')
      : pageCfg.mainPos === 'right'
        ? t('admin.layoutCfg.posRight')
        : t('admin.layoutCfg.posLeft')

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

      <LayoutConfigBar
        cfg={pageCfg}
        setCfg={setPageCfg}
        page={layoutPage}
        setPage={setLayoutPage}
      />
      {hiddenCount > 0 ? (
        <Card className="flex flex-wrap items-center gap-inline-md border-warn">
          <Text as="span" variant="caption">
            {t('admin.layoutCfg.hiddenWarning', {
              count: String(hiddenCount),
              regions: hiddenRegions
                .map((r) => t(`admin.region.${r}`))
                .join(t('common.listSeparator')),
            })}
          </Text>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              setPageCfg({ ...pageCfg, columns: 3 })
            }}
          >
            {t('admin.layoutCfg.makeThreeCol', { page: pageLabel })}
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
                  className={segBtn(layoutPage === 'home')}
                  onClick={() => {
                    setLayoutPage('home')
                  }}
                >
                  {t('admin.layout.previewHome')}
                </button>
                <button
                  type="button"
                  className={segBtn(layoutPage === 'record')}
                  onClick={() => {
                    setLayoutPage('record')
                  }}
                >
                  {t('admin.layout.previewRecord')}
                </button>
              </span>
            </div>
            <LayoutPreview
              widgets={page.widgets}
              menus={page.menus}
              cfg={pageCfg}
              page={layoutPage}
              selectedId={page.selectedId}
            />
            <Text as="span" muted variant="caption">
              {pageCfg.columns === 1
                ? t('admin.layout.previewNoteOneCol', { page: pageLabel })
                : t('admin.layout.previewNoteMultiCol', {
                    page: pageLabel,
                    columns: String(pageCfg.columns),
                    main: mainLabel,
                  })}
            </Text>
          </Stack>
        </div>
      )}
    </Stack>
  )
}

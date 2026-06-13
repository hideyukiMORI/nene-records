import type { ReactNode } from 'react'
import type { Menu } from '@/entities/menu'
import type { Widget } from '@/entities/widget'
import { useTranslation } from '@/shared/i18n'
import { bodyColumns, type LayoutConfig } from '@/shared/lib/layout-config'
import type { WidgetRegion } from '@/shared/lib/resolve-layout'

export type PreviewPage = 'home' | 'record'

export interface LayoutPreviewProps {
  widgets: Widget[]
  menus: Menu[]
  cfg: LayoutConfig
  page: PreviewPage
  selectedId: number | null
}

const SAMPLE_POSTS = ['Midnight Drift EP', 'Glass Harbor', 'Neon Tide', 'Slow Static', 'Aurora']
const SAMPLE_TAGS = ['House', 'Techno', 'Ambient', 'Dub', 'Lo-fi']

function MiniWidget({ w, menus, horizontal }: { w: Widget; menus: Menu[]; horizontal: boolean }) {
  const inner = (): ReactNode => {
    switch (w.widgetType) {
      case 'menu': {
        const menu = menus.find((m) => m.id === w.settings['menuId'])
        const links = menu ? ['Home', 'Releases', 'Artists'] : []
        if (menu === undefined) return <span className="text-caption text-danger">⚠ menu</span>
        return (
          <ul className={horizontal ? 'flex flex-row gap-inline-sm' : 'flex flex-col gap-stack-xs'}>
            {links.map((l) => (
              <li key={l} className="text-caption text-accent">
                {l}
              </li>
            ))}
          </ul>
        )
      }
      case 'recent-posts':
      case 'popular-posts':
        return (
          <ul className="flex flex-col gap-stack-xs">
            {SAMPLE_POSTS.slice(0, 4).map((p) => (
              <li key={p} className="text-caption text-accent">
                {p}
              </li>
            ))}
          </ul>
        )
      case 'search':
        return (
          <div className="rounded-sm border border-border px-inline-sm py-stack-xs text-caption text-text-muted">
            {typeof w.settings['placeholder'] === 'string' && w.settings['placeholder'] !== ''
              ? w.settings['placeholder']
              : '検索…'}
          </div>
        )
      case 'tag-cloud':
        return (
          <div className="flex flex-wrap gap-inline-xs">
            {SAMPLE_TAGS.map((tg) => (
              <span
                key={tg}
                className="rounded-full border border-border px-inline-sm text-caption"
              >
                {tg}
              </span>
            ))}
          </div>
        )
      case 'calendar':
        return (
          <div className="grid grid-cols-7 gap-px">
            {Array.from({ length: 28 }).map((_, i) => (
              <span
                key={i}
                className={`h-2 rounded-sm ${[4, 9, 17, 23].includes(i) ? 'bg-accent' : 'bg-surface-overlay'}`}
              />
            ))}
          </div>
        )
      case 'toc':
        return (
          <ul className="flex flex-col gap-stack-xs">
            {['概要', 'トラックリスト', 'クレジット'].map((tg) => (
              <li key={tg} className="text-caption text-accent">
                {tg}
              </li>
            ))}
          </ul>
        )
      default:
        return <div className="h-2 w-3/4 rounded-sm bg-surface-overlay" />
    }
  }
  return (
    <div className="min-w-0">
      {w.title !== null && w.title.trim() !== '' ? (
        <p className="mb-0.5 text-caption font-medium">{w.title}</p>
      ) : null}
      {inner()}
    </div>
  )
}

/** Compact mock of the public page reflecting current widgets and layout config. */
export function LayoutPreview({ widgets, menus, cfg, page, selectedId }: LayoutPreviewProps) {
  const { t } = useTranslation()
  const inRegion = (r: WidgetRegion): Widget[] =>
    widgets
      .filter((w) => w.region === r)
      .sort((a, b) => a.displayOrder - b.displayOrder || a.id - b.id)

  const cols: readonly (WidgetRegion | 'main')[] = page === 'record' ? bodyColumns(cfg) : ['main']

  const renderWidget = (w: Widget, horizontal: boolean) => (
    <div key={w.id} className={`rounded-sm p-1 ${w.id === selectedId ? 'ring-2 ring-accent' : ''}`}>
      <MiniWidget w={w} menus={menus} horizontal={horizontal} />
    </div>
  )

  return (
    <div className="overflow-hidden rounded-md border border-border bg-surface-raised text-text-primary">
      <header className="flex items-center gap-inline-md border-b border-border px-inline-md py-stack-sm">
        <span className="font-chrome text-body font-semibold">NeNe Records</span>
        <div className="ml-auto flex flex-wrap items-center gap-inline-md">
          {inRegion('header').map((w) => renderWidget(w, true))}
        </div>
      </header>
      <div
        className="grid gap-stack-md p-inline-md"
        style={{ gridTemplateColumns: cols.map((c) => (c === 'main' ? '2fr' : '1fr')).join(' ') }}
      >
        {cols.map((c) =>
          c === 'main' ? (
            <div key="main" className="flex flex-col gap-stack-xs">
              <p className="text-heading-sm">
                {page === 'record' ? 'Midnight Drift EP' : 'NeNe Records'}
              </p>
              <div className="h-2 w-11/12 rounded-sm bg-surface-overlay" />
              <div className="h-2 w-full rounded-sm bg-surface-overlay" />
              <div className="h-2 w-3/4 rounded-sm bg-surface-overlay" />
            </div>
          ) : (
            <div key={c} className="flex flex-col gap-stack-sm">
              {inRegion(c).length > 0 ? (
                inRegion(c).map((w) => renderWidget(w, false))
              ) : (
                <span className="text-center text-caption text-text-muted">
                  — {t(`admin.region.${c}`)} —
                </span>
              )}
            </div>
          ),
        )}
      </div>
      <footer className="flex flex-wrap items-center gap-inline-md border-t border-border px-inline-md py-stack-sm">
        {inRegion('footer').map((w) => renderWidget(w, true))}
      </footer>
    </div>
  )
}

import { useTranslation } from '@/shared/i18n'
import type { LayoutConfig } from '@/shared/lib/layout-config'
import { Card, Text } from '@/shared/ui'

export interface LayoutConfigBarProps {
  cfg: LayoutConfig
  setCfg: (cfg: LayoutConfig) => void
}

const segBtn = (active: boolean, disabled: boolean): string =>
  [
    'px-inline-md py-stack-xs text-body',
    disabled ? 'cursor-not-allowed text-text-muted opacity-50' : '',
    active
      ? 'bg-accent text-text-inverse'
      : 'bg-surface-raised text-text-primary hover:bg-surface-overlay',
  ].join(' ')

/** Record-detail layout structure: columns, main position, side swap. */
export function LayoutConfigBar({ cfg, setCfg }: LayoutConfigBarProps) {
  const { t } = useTranslation()
  const set = (patch: Partial<LayoutConfig>) => {
    setCfg({ ...cfg, ...patch })
  }
  const displayMain = cfg.columns < 3 && cfg.mainPos === 'center' ? 'left' : cfg.mainPos

  return (
    <Card className="flex flex-wrap items-center gap-inline-lg">
      <Text as="span" variant="heading-sm">
        {t('admin.layoutCfg.title')}
      </Text>

      <span className="flex items-center gap-inline-sm">
        <Text as="span" muted variant="caption">
          {t('admin.layoutCfg.columns')}
        </Text>
        <span className="inline-flex overflow-hidden rounded-md border border-border">
          {([1, 2, 3] as const).map((n) => (
            <button
              key={n}
              type="button"
              className={segBtn(cfg.columns === n, false)}
              onClick={() => {
                set({ columns: n })
              }}
            >
              {n}
            </button>
          ))}
        </span>
      </span>

      <span className="flex items-center gap-inline-sm">
        <Text as="span" muted variant="caption">
          {t('admin.layoutCfg.mainPos')}
        </Text>
        <span className="inline-flex overflow-hidden rounded-md border border-border">
          <button
            type="button"
            disabled={cfg.columns === 1}
            className={segBtn(displayMain === 'left', cfg.columns === 1)}
            onClick={() => {
              set({ mainPos: 'left' })
            }}
          >
            {t('admin.layoutCfg.posLeft')}
          </button>
          <button
            type="button"
            disabled={cfg.columns < 3}
            title={cfg.columns < 3 ? t('admin.layoutCfg.centerHint') : ''}
            className={segBtn(displayMain === 'center', cfg.columns < 3)}
            onClick={() => {
              set({ mainPos: 'center' })
            }}
          >
            {t('admin.layoutCfg.posCenter')}
          </button>
          <button
            type="button"
            disabled={cfg.columns === 1}
            className={segBtn(displayMain === 'right', cfg.columns === 1)}
            onClick={() => {
              set({ mainPos: 'right' })
            }}
          >
            {t('admin.layoutCfg.posRight')}
          </button>
        </span>
      </span>

      {cfg.columns >= 3 ? (
        <button
          type="button"
          className="rounded-md border border-border bg-surface-raised px-inline-md py-stack-xs text-body hover:bg-surface-overlay"
          onClick={() => {
            set({ swap: !cfg.swap })
          }}
        >
          {t('admin.layoutCfg.swap')}
        </button>
      ) : null}

      <span className="ml-auto">
        <Text as="span" muted variant="caption">
          {t('admin.layoutCfg.hint')}
        </Text>
      </span>
    </Card>
  )
}

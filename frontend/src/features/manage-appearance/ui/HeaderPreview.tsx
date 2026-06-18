import { useTranslation } from '@/shared/i18n'
import { hasCta, hasTopbarContent, type HeaderConfig } from '@/shared/lib/header-config'
import type { ThemeFlags } from '@/shared/lib/theme-customization'
import { IconMenu, IconSearch, IconSun } from '@/shared/ui/icons/Icons'

/**
 * A schematic, admin-styled live preview of the public header — reflects the
 * draft flags (layout + element show/hide) and header content (Top bar + CTA)
 * so admins immediately see their configuration take effect, instead of saving
 * blindly and checking the public site. Not pixel-perfect (uses admin tokens);
 * it mirrors arrangement and which elements appear, which is what's being
 * configured. See header-patterns.md §5.
 */
export function HeaderPreview({
  flags,
  header,
}: {
  flags: ThemeFlags | undefined
  header: HeaderConfig
}) {
  const { t } = useTranslation()
  const layout = flags?.headerLayout ?? 'nav-right'
  const showSearch = flags?.headerSearch !== 'hide'
  const showTheme = flags?.headerTheme !== 'hide'
  const showTagline = flags?.headerTagline !== 'hide'
  const navInline = layout !== 'minimal'
  const navBelow = layout === 'centered'
  const showMenu = layout === 'minimal'

  const nav = (
    <div className="flex items-center gap-inline-sm">
      {['Latest', 'Article', 'News'].map((label) => (
        <span
          key={label}
          className="rounded-full bg-surface-raised px-inline-sm py-px text-caption text-text-muted"
        >
          {label}
        </span>
      ))}
    </div>
  )

  const actions = (
    <div className="flex items-center gap-inline-sm text-text-muted">
      {showSearch ? <IconSearch size={15} /> : null}
      {showTheme ? <IconSun size={15} /> : null}
      {hasCta(header.cta) ? (
        <span className="rounded-full border border-accent bg-accent-weak px-inline-sm py-px text-caption font-semibold text-accent">
          {header.cta.label}
        </span>
      ) : null}
      {showMenu ? <IconMenu size={15} /> : null}
    </div>
  )

  const brand = (
    <div className="flex items-center gap-inline-sm">
      <span className="h-4 w-4 rounded-sm bg-accent" />
      <span className="flex flex-col leading-tight">
        <span className="font-chrome text-caption font-bold text-text-primary">Site name</span>
        {showTagline ? <span className="text-caption text-text-muted">Tagline</span> : null}
      </span>
    </div>
  )

  return (
    <div className="overflow-hidden rounded-sm border border-border">
      <span className="block bg-surface-raised px-inline-sm py-px font-chrome text-caption uppercase tracking-wide text-text-muted">
        {t('admin.headerPreview.label')}
      </span>

      {hasTopbarContent(header.topbar) ? (
        <div className="flex items-center justify-between gap-inline-md border-b border-border bg-surface-raised px-inline-md py-px text-caption text-text-muted">
          <span className="truncate">{header.topbar.infoText}</span>
          <span className="flex shrink-0 items-center gap-inline-sm">
            {header.topbar.phone !== '' ? <span>{header.topbar.phone}</span> : null}
            {header.topbar.email !== '' ? <span>{header.topbar.email}</span> : null}
          </span>
        </div>
      ) : null}

      {navBelow ? (
        <div className="flex flex-col items-center gap-stack-xs bg-surface px-inline-md py-stack-sm">
          <div className="flex w-full items-center justify-between">
            <span className="w-12" />
            {brand}
            {actions}
          </div>
          {nav}
        </div>
      ) : (
        <div className="flex items-center justify-between gap-inline-md bg-surface px-inline-md py-stack-sm">
          {brand}
          {navInline && layout === 'classic' ? nav : <span />}
          <div className="flex items-center gap-inline-md">
            {navInline && layout !== 'classic' ? nav : null}
            {actions}
          </div>
        </div>
      )}
    </div>
  )
}

import { Link } from 'react-router-dom'
import { usePublicMenus } from '@/entities/menu'
import { usePublicNavigationItems } from '@/entities/navigation-item'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'
import type { Widget } from '@/entities/widget'

export interface MenuWidgetProps {
  widget: Widget
}

/**
 * Renders a named menu's items as links. The menu is chosen by `settings.menuId`;
 * legacy widgets configured with `settings.location` fall back to the menu whose
 * slug matches that location (from the backfill).
 */
export function MenuWidget({ widget }: MenuWidgetProps) {
  const { t } = useTranslation()
  const { data: menusData } = usePublicMenus()
  const { data: navData } = usePublicNavigationItems()

  const menus = menusData?.items ?? []
  const settingsMenuId = widget.settings['menuId']
  const legacyLocation = widget.settings['location']

  let menuId: number | null = typeof settingsMenuId === 'number' ? settingsMenuId : null
  if (menuId === null && typeof legacyLocation === 'string') {
    menuId = menus.find((menu) => menu.slug === legacyLocation)?.id ?? null
  }

  const items =
    menuId === null ? [] : (navData?.items ?? []).filter((item) => item.menuId === menuId)

  if (items.length === 0) {
    return (
      <Text muted variant="caption">
        {t('widgets.menu.empty')}
      </Text>
    )
  }

  return (
    <ul className="flex flex-col gap-stack-xs">
      {items.map((item) => (
        <li key={item.id}>
          <Link to={item.url} className="text-body text-accent underline hover:no-underline">
            {item.label}
          </Link>
        </li>
      ))}
    </ul>
  )
}

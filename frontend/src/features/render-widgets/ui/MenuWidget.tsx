import { Link } from 'react-router-dom'
import { usePublicNavigationItems, type NavLocation } from '@/entities/navigation-item'
import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'
import type { Widget } from '@/entities/widget'

export interface MenuWidgetProps {
  widget: Widget
}

/** Renders navigation items for a configured location (default `side`) as links. */
export function MenuWidget({ widget }: MenuWidgetProps) {
  const { t } = useTranslation()
  const location: NavLocation =
    widget.settings['location'] === 'header' ||
    widget.settings['location'] === 'footer' ||
    widget.settings['location'] === 'side'
      ? widget.settings['location']
      : 'side'

  const { data } = usePublicNavigationItems()
  const items = (data?.items ?? []).filter((item) => item.location === location)

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

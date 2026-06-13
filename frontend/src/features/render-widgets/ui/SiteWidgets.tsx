import type { ReactNode } from 'react'
import type { Widget, WidgetType } from '@/entities/widget'
import { usePublicWidgets } from '@/entities/widget'
import type { ContentRegion } from '@/shared/lib/resolve-layout'
import { Stack, Text } from '@/shared/ui'
import { CalendarWidget } from './CalendarWidget'
import { MenuWidget } from './MenuWidget'
import { PopularPostsWidget } from './PopularPostsWidget'
import { RecentPostsWidget } from './RecentPostsWidget'
import { SearchWidget } from './SearchWidget'
import { TagCloudWidget } from './TagCloudWidget'
import { TocWidget } from './TocWidget'

export interface SiteWidgetsProps {
  region: ContentRegion
}

// Registry: widget type → renderer. Add new widget types here.
const WIDGET_REGISTRY: Record<WidgetType, (widget: Widget) => ReactNode> = {
  'recent-posts': (widget) => <RecentPostsWidget widget={widget} />,
  menu: (widget) => <MenuWidget widget={widget} />,
  toc: () => <TocWidget />,
  search: (widget) => <SearchWidget widget={widget} />,
  'tag-cloud': () => <TagCloudWidget />,
  'popular-posts': (widget) => <PopularPostsWidget widget={widget} />,
  calendar: () => <CalendarWidget />,
}

/** Renders all site widgets placed into a given region, in order. */
export function SiteWidgets({ region }: SiteWidgetsProps) {
  const { data } = usePublicWidgets()
  const widgets = (data?.items ?? []).filter((w) => w.region === region)

  if (widgets.length === 0) {
    return null
  }

  return (
    <Stack gap="lg">
      {widgets.map((widget) => (
        <section key={widget.id} aria-label={widget.title ?? widget.widgetType}>
          <Stack gap="xs">
            {widget.title !== null && widget.title !== '' ? (
              <Text as="h2" variant="heading-sm">
                {widget.title}
              </Text>
            ) : null}
            {WIDGET_REGISTRY[widget.widgetType](widget)}
          </Stack>
        </section>
      ))}
    </Stack>
  )
}

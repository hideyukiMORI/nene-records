import type { ReactNode } from 'react'
import type { Widget, WidgetType } from '@/entities/widget'
import { usePublicWidgets } from '@/entities/widget'
import type { WidgetRegion } from '@/shared/lib/resolve-layout'
import { Stack, Text } from '@/shared/ui'
import { CalendarWidget } from './CalendarWidget'
import { MenuWidget } from './MenuWidget'
import { PopularPostsWidget } from './PopularPostsWidget'
import { RecentPostsWidget } from './RecentPostsWidget'
import { SearchWidget } from './SearchWidget'
import { TagCloudWidget } from './TagCloudWidget'
import { TocWidget } from './TocWidget'

export type WidgetOrientation = 'vertical' | 'horizontal'

export interface SiteWidgetsProps {
  region: WidgetRegion
}

// Registry: widget type → renderer. Add new widget types here.
const WIDGET_REGISTRY: Record<
  WidgetType,
  (widget: Widget, orientation: WidgetOrientation) => ReactNode
> = {
  'recent-posts': (widget) => <RecentPostsWidget widget={widget} />,
  menu: (widget, orientation) => <MenuWidget widget={widget} orientation={orientation} />,
  toc: () => <TocWidget />,
  search: (widget) => <SearchWidget widget={widget} />,
  'tag-cloud': () => <TagCloudWidget />,
  'popular-posts': (widget) => <PopularPostsWidget widget={widget} />,
  calendar: () => <CalendarWidget />,
}

// Header/footer are horizontal bars; side columns stack vertically.
function orientationForRegion(region: WidgetRegion): WidgetOrientation {
  return region === 'header' || region === 'footer' ? 'horizontal' : 'vertical'
}

/**
 * Renders a single widget's body without the section/title chrome — for hosts
 * that provide their own heading (footer columns, #772).
 */
export function SiteWidgetBody({
  widget,
  orientation = 'vertical',
}: {
  widget: Widget
  orientation?: WidgetOrientation
}) {
  return <>{WIDGET_REGISTRY[widget.widgetType](widget, orientation)}</>
}

/** Renders all site widgets placed into a given region, in order. */
export function SiteWidgets({ region }: SiteWidgetsProps) {
  const { data } = usePublicWidgets()
  const widgets = (data?.items ?? []).filter((w) => w.region === region)

  if (widgets.length === 0) {
    return null
  }

  const orientation = orientationForRegion(region)
  const containerClass =
    orientation === 'horizontal'
      ? 'flex flex-wrap items-center gap-inline-md'
      : 'flex flex-col gap-stack-lg'

  return (
    <div className={containerClass}>
      {widgets.map((widget) => (
        <section key={widget.id} aria-label={widget.title ?? widget.widgetType}>
          <Stack gap="xs">
            {widget.title !== null && widget.title !== '' ? (
              <Text as="h2" variant="heading-sm">
                {widget.title}
              </Text>
            ) : null}
            {WIDGET_REGISTRY[widget.widgetType](widget, orientation)}
          </Stack>
        </section>
      ))}
    </div>
  )
}

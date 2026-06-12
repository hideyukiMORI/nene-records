import type { ReactNode } from 'react'
import type { Widget, WidgetType } from '@/entities/widget'
import { usePublicWidgets } from '@/entities/widget'
import type { ContentRegion } from '@/shared/lib/resolve-layout'
import { Stack, Text } from '@/shared/ui'
import { RecentPostsWidget } from './RecentPostsWidget'

export interface SiteWidgetsProps {
  region: ContentRegion
}

// Registry: widget type → renderer. Add new widget types here.
const WIDGET_REGISTRY: Record<WidgetType, (widget: Widget) => ReactNode> = {
  'recent-posts': (widget) => <RecentPostsWidget widget={widget} />,
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

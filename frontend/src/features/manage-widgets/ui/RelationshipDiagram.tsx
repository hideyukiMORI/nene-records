import { useTranslation } from '@/shared/i18n'
import { Text } from '@/shared/ui'

/** Menu → Menu widget → Region. Explains that "build" and "place" are separate. */
export function RelationshipDiagram() {
  const { t } = useTranslation()

  const node = (
    titleKey: Parameters<typeof t>[0],
    descKey: Parameters<typeof t>[0],
    accent: boolean,
  ) => (
    <div
      className={[
        'flex-1 rounded-md border px-inline-md py-stack-sm',
        accent ? 'border-accent bg-accent-weak' : 'border-border bg-surface-raised',
      ].join(' ')}
    >
      <Text as="div" variant="body">
        {t(titleKey)}
      </Text>
      <Text as="div" muted variant="caption">
        {t(descKey)}
      </Text>
    </div>
  )

  return (
    <div className="flex flex-col items-stretch gap-stack-sm sm:flex-row sm:items-center">
      {node('admin.layout.diagram.menu', 'admin.layout.diagram.menuDesc', false)}
      <span aria-hidden className="text-center text-text-muted">
        →
      </span>
      {node('admin.layout.diagram.widget', 'admin.layout.diagram.widgetDesc', true)}
      <span aria-hidden className="text-center text-text-muted">
        →
      </span>
      {node('admin.layout.diagram.region', 'admin.layout.diagram.regionDesc', false)}
    </div>
  )
}

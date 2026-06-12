import { SiteWidgets } from '@/features/render-widgets'
import type { Entity } from '@/entities/entity'
import { layoutRegions, type PublicLayoutKey, regionForLayout } from '@/shared/lib/resolve-layout'
import type { PublicFieldRow } from '../hooks/use-public-view-entity-record-page'
import { PublicRecordFieldList } from './PublicRecordFieldList'

export interface PublicRecordRegionGridProps {
  layout: PublicLayoutKey
  entity: Entity
  fieldRows: PublicFieldRow[]
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
}

// Column templates per multi-column layout (main is the wide column).
const GRID_CLASS: Partial<Record<PublicLayoutKey, string>> = {
  'two-col': 'lg:grid-cols-[2fr_1fr]',
  'three-col': 'lg:grid-cols-[2fr_1fr_1fr]',
}

/**
 * Distributes field rows into the regions rendered by a multi-column layout.
 * Fields whose region the layout does not render fall back to `main`.
 */
export function PublicRecordRegionGrid({
  layout,
  entity,
  fieldRows,
  entityTypeSlugById,
  entityTypePatternById,
}: PublicRecordRegionGridProps) {
  const regions = layoutRegions(layout)

  return (
    <div className={`grid grid-cols-1 gap-stack-lg ${GRID_CLASS[layout] ?? ''}`}>
      {regions.map((region) => {
        const rows = fieldRows.filter((row) => regionForLayout(row.region, layout) === region)

        return (
          <div key={region} data-region={region} className="flex flex-col gap-stack-lg">
            {rows.length > 0 ? (
              <PublicRecordFieldList
                entity={entity}
                fieldRows={rows}
                entityTypeSlugById={entityTypeSlugById}
                entityTypePatternById={entityTypePatternById}
              />
            ) : null}
            {/* Site widgets live in the secondary columns, not the main content. */}
            {region !== 'main' ? <SiteWidgets region={region} /> : null}
          </div>
        )
      })}
    </div>
  )
}

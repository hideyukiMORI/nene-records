import type { Entity } from '@/entities/entity'
import { isMarkdownBodyField } from '@/shared/lib/is-markdown-body-field'
import { SanitizedHtml, Text } from '@/shared/ui'
import { PublicMarkdownContent } from '@/shared/ui/markdown'
import type { PublicFieldRow } from '../hooks/use-public-view-entity-record-page'
import { PublicRelationFieldDisplay } from './PublicRelationFieldDisplay'

export interface PublicRecordFieldListProps {
  entity: Entity
  fieldRows: PublicFieldRow[]
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
}

/**
 * Renders a list of resolved field rows as a definition list. Stateless — the
 * caller handles loading / error / empty and any region grouping.
 */
export function PublicRecordFieldList({
  entity,
  fieldRows,
  entityTypeSlugById,
  entityTypePatternById,
}: PublicRecordFieldListProps) {
  return (
    <dl className="flex flex-col gap-stack-md">
      {fieldRows.map((row) => {
        if (row.kind === 'relation') {
          return (
            <PublicRelationFieldDisplay
              key={row.fieldDef.fieldKey}
              entityId={Number(entity.id)}
              fieldDef={row.fieldDef}
              entityTypeSlugById={entityTypeSlugById}
              entityTypePatternById={entityTypePatternById}
            />
          )
        }

        return (
          <div key={row.fieldKey} className="flex flex-col gap-stack-xs">
            <Text as="dt" variant="heading-sm">
              {row.fieldKey}
            </Text>
            {row.dataType === 'html' ? (
              <dd>
                <SanitizedHtml html={row.displayValue === '—' ? '' : row.displayValue} />
              </dd>
            ) : isMarkdownBodyField(row.fieldKey) && row.dataType === 'text' ? (
              <dd>
                <PublicMarkdownContent
                  markdown={row.displayValue === '—' ? '' : row.displayValue}
                />
              </dd>
            ) : (
              <Text as="dd" muted>
                {row.displayValue}
              </Text>
            )}
          </div>
        )
      })}
    </dl>
  )
}

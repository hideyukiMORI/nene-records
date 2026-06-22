import type { Entity } from '@/entities/entity'
import { parseBundleDocument } from '@/shared/lib/bundle-document'
import { isMarkdownBodyField } from '@/shared/lib/is-markdown-body-field'
import { SandboxedBundle, SanitizedHtml, Text } from '@/shared/ui'
import { BlocksRenderer } from '@/shared/ui/blocks'
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

        // Markdown content (a `markdown` field, or a legacy text `body`) renders
        // as the article's prose reading column — no field-key label, so it reads
        // like a magazine article rather than a labelled definition row.
        if (
          row.dataType === 'markdown' ||
          (row.dataType === 'text' && isMarkdownBodyField(row.fieldKey))
        ) {
          return (
            <div key={row.fieldKey} className="prose">
              <PublicMarkdownContent markdown={row.displayValue === '—' ? '' : row.displayValue} />
            </div>
          )
        }

        // Typed post blocks (#486) render as the article body via first-party,
        // theme-following block renderers — no field-key label.
        if (row.dataType === 'blocks') {
          return (
            <div key={row.fieldKey} className="prose">
              <BlocksRenderer documentJson={row.displayValue === '—' ? '' : row.displayValue} />
            </div>
          )
        }

        // Custom-page bundle (#311 / WS3): the sandboxed iframe IS the page (no
        // field-key label). Its crawlable seoText twin is projected sr-only for
        // assistive tech + JS crawlers; the SSR document carries the same text
        // server-rendered for no-JS / basic crawlers.
        if (row.dataType === 'bundle') {
          const bundle = parseBundleDocument(row.displayValue === '—' ? '' : row.displayValue)
          return (
            <div key={row.fieldKey}>
              {bundle.seoText.trim() !== '' ? (
                <div className="sr-only">
                  <PublicMarkdownContent markdown={bundle.seoText} />
                </div>
              ) : null}
              <SandboxedBundle html={bundle.html} />
            </div>
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

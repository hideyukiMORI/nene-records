import { useMemo } from 'react'
import { Link, Navigate, useLocation, useParams } from 'react-router-dom'
import { toEntityId, useEntity } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { CommentSection, useCommentSection } from '@/features/comment-section'
import { usePublicBrowseEntityRecordsPage } from '@/features/public-browse-entity-records'
import {
  PublicRecordDetailView,
  type PublicFieldRow,
  usePublicViewEntityRecordPage,
} from '@/features/public-view-entity-record'
import { PageContentContext } from '@/features/render-widgets'
import { useTranslation } from '@/shared/i18n'
import { findEntityTypeBySlug } from '@/shared/lib/find-entity-type-by-slug'
import { isMarkdownBodyField } from '@/shared/lib/is-markdown-body-field'
import { type PublicLayoutKey, resolveLayout } from '@/shared/lib/resolve-layout'
import {
  DEFAULT_PERMALINK_PATTERN,
  extractEntityKeyFromSplat,
  resolvePermalink,
} from '@/shared/lib/resolve-permalink'
import {
  IconArrowLeft,
  IconArrowUpRight,
  IconInbox,
  IconUser,
} from '@/shared/ui/icons/magazine-icons'
import { InlineTableOfContents } from '@/shared/ui/markdown'
import { useEntityIdBySlug } from './hooks/use-entity-id-by-slug'
import { PublicSiteShell } from './PublicSiteShell'
import { usePublicSite, type PublicSite } from './public-site-context'

// ── Presentation helpers ──────────────────────────────────────────────────────

function formatPublishedDate(iso: string | null): string {
  if (iso === null || iso === '') {
    return ''
  }
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

function humanizeSlug(slug: string | null | undefined): string {
  if (slug == null || slug.trim() === '') {
    return ''
  }
  return slug
    .split('-')
    .filter((part) => part !== '')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

/** A wrapper that gives the record page the magazine chrome + reading column. */
function RecordShellMessage({
  site,
  entityTypeSlug,
  title,
  description,
  icon = false,
}: {
  site: PublicSite
  entityTypeSlug: string | null
  title: string
  description: string
  icon?: boolean
}) {
  return (
    <PublicSiteShell site={site} activeTypeSlug={entityTypeSlug} withSidebar={false}>
      <div className="pagehead">
        <Link className="backlink" to="/">
          <IconArrowLeft size={16} /> Home
        </Link>
        <div className="empty">
          {icon ? (
            <span className="empty__icon">
              <IconInbox size={26} />
            </span>
          ) : null}
          <h3 className="empty__title">{title}</h3>
          <p className="empty__text">{description}</p>
          <Link className="btn btn--ghost" to="/">
            Back to latest
          </Link>
        </div>
      </div>
    </PublicSiteShell>
  )
}

/** "More from {type}" — related records of the same type. */
function RelatedRecords({
  entityTypeSlug,
  entityTypeName,
  currentEntityId,
}: {
  entityTypeSlug: string
  entityTypeName: string
  currentEntityId: number
}) {
  const { items } = usePublicBrowseEntityRecordsPage(entityTypeSlug, 0)
  const related = items.filter((item) => item.id !== currentEntityId).slice(0, 3)

  if (related.length === 0) {
    return null
  }

  return (
    <section className="related" aria-labelledby="related-h">
      <div className="section__head">
        <div>
          <p className="eyebrow">Keep reading</p>
          <h2 className="section__title" id="related-h">
            More from {entityTypeName}
          </h2>
        </div>
        <Link className="section__link" to={`/${entityTypeSlug}`}>
          All {entityTypeName.toLowerCase()} <IconArrowUpRight size={15} />
        </Link>
      </div>
      <div className="cardgrid">
        {related.map((item) => (
          <article key={item.id} className="card">
            <Link to={item.publicUrl}>
              <div
                className="eyecatch card__media"
                role="img"
                aria-label="eyecatch · 16:10 placeholder"
                data-label="eyecatch · 16:10"
              />
            </Link>
            <div className="card__metarow">
              <Link className="tbadge" to={`/${entityTypeSlug}`}>
                {entityTypeName.toLowerCase()}
              </Link>
              {item.publishedLabel !== '' ? (
                <span className="meta">{item.publishedLabel}</span>
              ) : null}
            </div>
            <h3 className="card__title">
              <Link to={item.publicUrl}>{item.label}</Link>
            </h3>
          </article>
        ))}
      </div>
    </section>
  )
}

// ── Canonical redirect helper ─────────────────────────────────────────────────

/**
 * Compare the current URL to the entity's canonical URL.
 * Returns the canonical URL if they differ (→ redirect needed), otherwise null.
 */
function useCanonicalRedirect(
  pattern: string | null | undefined,
  entityTypeSlug: string,
  entityId: number,
  entitySlug: string | null,
  publishedAt: string | null,
): string | null {
  const { pathname } = useLocation()
  const canonical = resolvePermalink(pattern ?? DEFAULT_PERMALINK_PATTERN, {
    typeSlug: entityTypeSlug,
    entitySlug,
    entityId,
    publishedAt,
  })
  return pathname !== canonical ? canonical : null
}

// ── Content renderer (entity already resolved to a numeric ID) ────────────────

function PublicRecordDetailContent({
  entityTypeSlug,
  entityTypeName,
  entityTypeId,
  entityId,
  entityTypeSlugById,
  entityTypePatternById,
  currentPattern,
  entityTypeDefaultLayout,
}: {
  entityTypeSlug: string
  entityTypeName: string
  entityTypeId: number
  entityId: number
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
  currentPattern: string | null | undefined
  entityTypeDefaultLayout: PublicLayoutKey
}) {
  const site = usePublicSite()
  const { entity, fieldRows, isLoading, isError, errorTitle, refetch } =
    usePublicViewEntityRecordPage(entityTypeId, entityId)
  const commentSection = useCommentSection(entityId)

  const variant = resolveLayout(entity?.layout ?? null, entityTypeDefaultLayout)
  const isMultiColLayout = variant === 'two-col' || variant === 'three-col'
  // Single-column themed layouts have no sidebar to host the TOC widget, so they
  // get an auto inline TOC instead. `bare`/`custom` are author-controlled — skip.
  const isInlineTocLayout = variant === 'standard' || variant === 'full'

  // The page's markdown body feeds region widgets (e.g. the TOC widget) so they
  // derive from the same content the main column renders.
  const pageMarkdown = useMemo(
    () =>
      fieldRows
        .filter(
          (row) =>
            row.kind === 'scalar' &&
            (row.dataType === 'markdown' ||
              (row.dataType === 'text' && isMarkdownBodyField(row.fieldKey))) &&
            row.displayValue !== '—',
        )
        .map((row) => (row.kind === 'scalar' ? row.displayValue : ''))
        .join('\n\n'),
    [fieldRows],
  )

  // Magazine header: lift the record's title into the masthead and drop the
  // matching field row from the body so it isn't rendered twice.
  const titleRow = fieldRows.find(
    (row): row is Extract<PublicFieldRow, { kind: 'scalar' }> =>
      row.kind === 'scalar' && row.fieldKey === 'title' && row.displayValue !== '—',
  )
  const resolvedTitle =
    titleRow?.displayValue.trim() ||
    entity?.metaTitle?.trim() ||
    humanizeSlug(entity?.slug) ||
    `Record #${String(entityId)}`
  const bodyRows = useMemo(
    () => fieldRows.filter((row) => !(row.kind === 'scalar' && row.fieldKey === 'title')),
    [fieldRows],
  )
  const publishedLabel = formatPublishedDate(entity?.publishedAt ?? null)

  // Redirect if the current URL doesn't match the canonical URL for this entity.
  // This handles pattern changes (e.g. /{type}/{id} → /{type}/{slug}) transparently.
  const redirect = useCanonicalRedirect(
    currentPattern,
    entityTypeSlug,
    entityId,
    entity?.slug ?? null,
    entity?.publishedAt ?? null,
  )
  if (!isLoading && !isError && entity !== null && redirect !== null) {
    return <Navigate to={redirect} replace />
  }

  // `bare` escapes all chrome so a record can ship a fully custom page.
  if (variant === 'bare') {
    return (
      <PageContentContext.Provider value={pageMarkdown}>
        <PublicRecordDetailView
          entity={entity}
          fieldRows={fieldRows}
          entityTypeSlugById={entityTypeSlugById}
          entityTypePatternById={entityTypePatternById}
          isLoading={isLoading}
          isError={isError}
          errorTitle={errorTitle}
          onRetry={() => {
            void refetch()
          }}
        />
      </PageContentContext.Provider>
    )
  }

  // Multi-column layouts surface the global widget sidebar as the second column
  // of the shell's top-level `.layout` grid — a sibling of the article, not a
  // region nested inside it. `align-items: start` then aligns the sidebar's top
  // with the article header (no head-height gap). The body stays a single prose
  // reading column. The provider wraps the whole shell so sidebar widgets (e.g.
  // the TOC widget) still read this page's markdown.
  return (
    <PageContentContext.Provider value={pageMarkdown}>
      <PublicSiteShell
        site={site}
        activeTypeSlug={entityTypeSlug}
        withSidebar={isMultiColLayout}
        withAside={variant === 'three-col'}
      >
        <article className="article">
          <Link className="backlink" to={`/${entityTypeSlug}`}>
            <IconArrowLeft size={16} /> Back to {entityTypeName}
          </Link>
          <header className="article__head">
            <div className="article__metarow">
              <Link className="tbadge" to={`/${entityTypeSlug}`}>
                {entityTypeName.toLowerCase()}
              </Link>
            </div>
            <h1 className="article__title">{resolvedTitle}</h1>
            {publishedLabel !== '' ? (
              <div className="article__byline">
                <span className="article__avatar" aria-hidden="true">
                  <IconUser size={16} />
                </span>
                <span>Published {publishedLabel}</span>
              </div>
            ) : null}
          </header>

          {isInlineTocLayout && !isLoading && !isError && entity !== null ? (
            <InlineTableOfContents markdown={pageMarkdown} />
          ) : null}
          <PublicRecordDetailView
            entity={entity}
            fieldRows={bodyRows}
            entityTypeSlugById={entityTypeSlugById}
            entityTypePatternById={entityTypePatternById}
            isLoading={isLoading}
            isError={isError}
            errorTitle={errorTitle}
            onRetry={() => {
              void refetch()
            }}
          />

          {entity !== null && !isLoading && !isError ? (
            <>
              <section className="comments">
                <CommentSection {...commentSection} />
              </section>
              <RelatedRecords
                entityTypeSlug={entityTypeSlug}
                entityTypeName={entityTypeName}
                currentEntityId={entityId}
              />
            </>
          ) : null}
        </article>
      </PublicSiteShell>
    </PageContentContext.Provider>
  )
}

// ── Slug → ID resolver with old-pattern fallback ──────────────────────────────

/**
 * Resolves a slug (from the current pattern) to an entity ID.
 * If not found, tries the previous permalink pattern's key as a fallback.
 * Returns null when neither lookup finds anything.
 */
function useEntityIdWithFallback(
  entityTypeId: number,
  currentSlug: string,
  previousPattern: string | null | undefined,
  splat: string,
): { entityId: number | null; isLoading: boolean; isError: boolean } {
  // Primary: look up by slug
  const primary = useEntityIdBySlug(entityTypeId, currentSlug)

  // Fallback key from previous pattern (only when primary fails)
  const previousKey = useMemo(
    () => (previousPattern != null ? extractEntityKeyFromSplat(previousPattern, splat) : null),
    [previousPattern, splat],
  )
  const fallbackId = previousKey?.kind === 'id' ? previousKey.id : null
  const fallbackSlug = previousKey?.kind === 'slug' ? previousKey.slug : ''

  // Fetch the fallback entity by ID (only when primary slug not found and fallback is ID-based)
  const fallbackEntityQuery = useEntity(toEntityId(fallbackId ?? 0), {
    enabled: primary.entityId === null && !primary.isLoading && fallbackId !== null,
  })

  // Fallback slug lookup (only when primary slug not found and fallback is slug-based)
  const fallbackSlugQuery = useEntityIdBySlug(entityTypeId, fallbackSlug)
  // Only use fallback slug result when primary has finished and returned nothing
  const useFallbackSlug =
    primary.entityId === null && !primary.isLoading && previousKey?.kind === 'slug'

  if (primary.isLoading) return { entityId: null, isLoading: true, isError: false }
  if (primary.entityId !== null)
    return { entityId: primary.entityId, isLoading: false, isError: false }

  // Primary failed — check fallback
  if (fallbackId !== null) {
    if (fallbackEntityQuery.isLoading) return { entityId: null, isLoading: true, isError: false }
    if (fallbackEntityQuery.data !== undefined) {
      return { entityId: Number(fallbackEntityQuery.data.id), isLoading: false, isError: false }
    }
    return { entityId: null, isLoading: false, isError: false }
  }

  if (useFallbackSlug) {
    if (fallbackSlugQuery.isLoading) return { entityId: null, isLoading: true, isError: false }
    if (fallbackSlugQuery.entityId !== null) {
      return { entityId: fallbackSlugQuery.entityId, isLoading: false, isError: false }
    }
  }

  return { entityId: null, isLoading: false, isError: primary.isError }
}

// ── Slug-based entry (uses hook above) ───────────────────────────────────────

function PublicRecordDetailBySlug({
  entityTypeSlug,
  entityTypeName,
  entityTypeId,
  entitySlug,
  entityTypeSlugById,
  entityTypePatternById,
  currentPattern,
  previousPattern,
  splat,
  entityTypeDefaultLayout,
}: {
  entityTypeSlug: string
  entityTypeName: string
  entityTypeId: number
  entitySlug: string
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
  currentPattern: string | null | undefined
  previousPattern: string | null | undefined
  splat: string
  entityTypeDefaultLayout: PublicLayoutKey
}) {
  const { t } = useTranslation()
  const site = usePublicSite()
  const { entityId, isLoading, isError } = useEntityIdWithFallback(
    entityTypeId,
    entitySlug,
    previousPattern,
    splat,
  )

  if (isLoading) {
    return (
      <RecordShellMessage
        site={site}
        entityTypeSlug={entityTypeSlug}
        title="Loading…"
        description="Fetching this record."
      />
    )
  }
  if (isError || entityId === null) {
    return (
      <RecordShellMessage
        site={site}
        entityTypeSlug={entityTypeSlug}
        title={t('public.record.notFound.title')}
        description={t('public.record.notFound.description', { slug: entitySlug })}
        icon
      />
    )
  }

  return (
    <PublicRecordDetailContent
      entityTypeSlug={entityTypeSlug}
      entityTypeName={entityTypeName}
      entityTypeId={entityTypeId}
      entityId={entityId}
      entityTypeSlugById={entityTypeSlugById}
      entityTypePatternById={entityTypePatternById}
      currentPattern={currentPattern}
      entityTypeDefaultLayout={entityTypeDefaultLayout}
    />
  )
}

// ── ID-based entry with old-pattern fallback ──────────────────────────────────

function PublicRecordDetailById({
  entityTypeSlug,
  entityTypeName,
  entityTypeId,
  entityId,
  entityTypeSlugById,
  entityTypePatternById,
  currentPattern,
  entityTypeDefaultLayout,
}: {
  entityTypeSlug: string
  entityTypeName: string
  entityTypeId: number
  entityId: number
  entityTypeSlugById: Record<number, string>
  entityTypePatternById: Record<number, string | null | undefined>
  currentPattern: string | null | undefined
  entityTypeDefaultLayout: PublicLayoutKey
}) {
  return (
    <PublicRecordDetailContent
      entityTypeSlug={entityTypeSlug}
      entityTypeName={entityTypeName}
      entityTypeId={entityTypeId}
      entityId={entityId}
      entityTypeSlugById={entityTypeSlugById}
      entityTypePatternById={entityTypePatternById}
      currentPattern={currentPattern}
      entityTypeDefaultLayout={entityTypeDefaultLayout}
    />
  )
}

// ── Page root ─────────────────────────────────────────────────────────────────

export function PublicRecordDetailPage() {
  // React Router v6: splat param is '*'
  const { entityTypeSlug = '', '*': splat = '' } = useParams()
  const { t } = useTranslation()
  const site = usePublicSite()

  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })
  const entityType = useMemo(
    () => findEntityTypeBySlug(entityTypeQuery.data?.items ?? [], entityTypeSlug),
    [entityTypeQuery.data?.items, entityTypeSlug],
  )
  const entityTypeSlugById = useMemo(
    (): Record<number, string> =>
      Object.fromEntries(
        (entityTypeQuery.data?.items ?? []).map((item) => [Number(item.id), item.slug]),
      ),
    [entityTypeQuery.data?.items],
  )

  const entityTypePatternById = useMemo(
    (): Record<number, string | null | undefined> =>
      Object.fromEntries(
        (entityTypeQuery.data?.items ?? []).map((item) => [Number(item.id), item.permalinkPattern]),
      ),
    [entityTypeQuery.data?.items],
  )

  if (entityTypeQuery.isLoading) {
    return (
      <RecordShellMessage
        site={site}
        entityTypeSlug={entityTypeSlug}
        title="Loading…"
        description="Fetching this record."
      />
    )
  }

  if (entityType === undefined) {
    return (
      <RecordShellMessage
        site={site}
        entityTypeSlug={null}
        title={t('public.entityType.notFound.title')}
        description={t('public.entityType.notFound.description', { slug: entityTypeSlug })}
        icon
      />
    )
  }

  const entityTypeId = Number(entityType.id)
  const key = extractEntityKeyFromSplat(entityType.permalinkPattern, splat)

  if (key.kind === 'id') {
    return (
      <PublicRecordDetailById
        entityTypeSlug={entityTypeSlug}
        entityTypeName={entityType.name}
        entityTypeId={entityTypeId}
        entityId={key.id}
        entityTypeSlugById={entityTypeSlugById}
        entityTypePatternById={entityTypePatternById}
        currentPattern={entityType.permalinkPattern}
        entityTypeDefaultLayout={entityType.defaultLayout}
      />
    )
  }

  return (
    <PublicRecordDetailBySlug
      entityTypeSlug={entityTypeSlug}
      entityTypeName={entityType.name}
      entityTypeId={entityTypeId}
      entitySlug={key.slug}
      entityTypeSlugById={entityTypeSlugById}
      entityTypePatternById={entityTypePatternById}
      currentPattern={entityType.permalinkPattern}
      previousPattern={entityType.previousPermalinkPattern}
      splat={splat}
      entityTypeDefaultLayout={entityType.defaultLayout}
    />
  )
}

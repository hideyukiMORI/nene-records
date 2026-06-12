import { useMemo } from 'react'
import { Link, Navigate, useLocation, useParams } from 'react-router-dom'
import { toEntityId, useEntity } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { CommentSection, useCommentSection } from '@/features/comment-section'
import {
  PublicRecordDetailView,
  usePublicViewEntityRecordPage,
} from '@/features/public-view-entity-record'
import { useTranslation } from '@/shared/i18n'
import { findEntityTypeBySlug } from '@/shared/lib/find-entity-type-by-slug'
import { type PublicLayoutKey, resolveLayout } from '@/shared/lib/resolve-layout'
import {
  DEFAULT_PERMALINK_PATTERN,
  extractEntityKeyFromSplat,
  resolvePermalink,
} from '@/shared/lib/resolve-permalink'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'
import { useEntityIdBySlug } from './hooks/use-entity-id-by-slug'
import { PublicLayout } from './PublicLayout'
import { usePublicSite } from './public-site-context'

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

  return (
    <PublicLayout variant={variant} site={site}>
      <Stack gap="md">
        <Stack gap="sm">
          <Link to={`/${entityTypeSlug}`}>
            <Button variant="secondary" size="sm">
              Back to {entityTypeName}
            </Button>
          </Link>
          <Text as="h1" variant="heading-md">
            {entityTypeName}
          </Text>
        </Stack>
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
        {entity !== null && !isLoading && !isError ? <CommentSection {...commentSection} /> : null}
      </Stack>
    </PublicLayout>
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
      <PublicLayout variant="standard" site={site}>
        <Text muted>Loading…</Text>
      </PublicLayout>
    )
  }
  if (isError || entityId === null) {
    return (
      <PublicLayout variant="standard" site={site}>
        <EmptyState
          title={t('public.record.notFound.title')}
          description={t('public.record.notFound.description', { slug: entitySlug })}
        />
      </PublicLayout>
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

/**
 * Fetch the entity by ID with a fallback to the previous slug-pattern key.
 * Covers the case where the old URL used /{type}/{id} but the new pattern is slug-based.
 */
// Note: when the current pattern is ID-based but an old URL uses a slug
// (e.g. /posts/my-article), extractEntityKeyFromSplat returns kind:'slug' so
// it goes to PublicRecordDetailBySlug, not here. This component only handles
// the case where the current key is already a numeric ID.
// The canonical-URL redirect in PublicRecordDetailContent handles any pattern
// changes that keep the same key type (id→id or slug→slug).
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
      <PublicLayout variant="standard" site={site}>
        <Text muted>Loading…</Text>
      </PublicLayout>
    )
  }

  if (entityType === undefined) {
    return (
      <PublicLayout variant="standard" site={site}>
        <EmptyState
          title={t('public.entityType.notFound.title')}
          description={t('public.entityType.notFound.description', { slug: entityTypeSlug })}
        />
      </PublicLayout>
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

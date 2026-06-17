import { useMemo } from 'react'
import { usePublicLatestEntities } from '@/entities/entity'
import { useEntityTypeList } from '@/entities/entity-type'
import { resolvePermalink } from '@/shared/lib/resolve-permalink'

/** A single article in the "Latest records" feed. */
export interface HomeFeedItem {
  id: number
  title: string
  excerpt: string
  publishedLabel: string
  typeSlug: string
  typeName: string
  href: string
  /** Placeholder caption for the eyecatch frame (real images come later). */
  eyecatchLabel: string
}

/** An entrance card in the "Browse by type" section. */
export interface HomeTypeItem {
  slug: string
  name: string
  href: string
}

export interface PublicHomeViewModel {
  featured: HomeFeedItem | null
  rest: HomeFeedItem[]
  types: HomeTypeItem[]
  totalPublished: number
  typeCount: number
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  refetch: () => Promise<void>
}

function humanizeSlug(slug: string | null): string {
  if (slug === null || slug.trim() === '') {
    return ''
  }
  return slug
    .split('-')
    .filter((part) => part !== '')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

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

/**
 * Composes the public home page from two public reads: the entity-type list
 * (entrances + permalink patterns) and the cross-type latest published feed.
 * Titles/excerpts come from each record's SEO meta (metaTitle/metaDescription),
 * falling back to a humanized slug, so no per-type text-field queries are needed.
 */
export function usePublicHomePage(): PublicHomeViewModel {
  const entityTypeQuery = useEntityTypeList({ limit: 100, offset: 0 })
  const feedQuery = usePublicLatestEntities({ limit: 13 })

  const types = useMemo(
    (): HomeTypeItem[] =>
      (entityTypeQuery.data?.items ?? []).map((type) => ({
        slug: type.slug,
        name: type.name,
        href: `/${type.slug}`,
      })),
    [entityTypeQuery.data?.items],
  )

  const feed = useMemo((): HomeFeedItem[] => {
    const typeList = entityTypeQuery.data?.items ?? []
    const entities = feedQuery.data?.items ?? []

    return entities.map((entity, index): HomeFeedItem => {
      const type = typeList.find((item) => Number(item.id) === entity.entityTypeId)
      const typeSlug = type?.slug ?? 'record'
      const id = Number(entity.id)
      const metaTitle = entity.metaTitle?.trim() ?? ''
      const title =
        metaTitle !== '' ? metaTitle : humanizeSlug(entity.slug) || `Record #${String(id)}`

      return {
        id,
        title,
        excerpt: entity.excerpt?.trim() ?? '',
        publishedLabel: formatPublishedDate(entity.publishedAt),
        typeSlug,
        typeName: type?.name ?? typeSlug,
        href: resolvePermalink(type?.permalinkPattern, {
          typeSlug,
          entitySlug: entity.slug,
          entityId: id,
          publishedAt: entity.publishedAt,
        }),
        // The featured (first) item shows a 4:3 frame; cards show 16:10.
        eyecatchLabel: index === 0 ? 'eyecatch · 4:3' : 'eyecatch · 16:10',
      }
    })
  }, [entityTypeQuery.data?.items, feedQuery.data?.items])

  const [featured = null, ...rest] = feed

  return {
    featured,
    rest,
    types,
    totalPublished: feedQuery.data?.total ?? 0,
    typeCount: types.length,
    isLoading: entityTypeQuery.isLoading || feedQuery.isLoading,
    isError: entityTypeQuery.isError || feedQuery.isError,
    errorTitle: entityTypeQuery.error?.title ?? feedQuery.error?.title ?? null,
    refetch: async () => {
      await Promise.all([entityTypeQuery.refetch(), feedQuery.refetch()])
    },
  }
}

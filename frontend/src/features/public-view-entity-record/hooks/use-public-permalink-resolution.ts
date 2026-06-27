import { useQuery, type UseQueryResult } from '@tanstack/react-query'
import {
  type PublicPermalinkResolutionDto,
  publicPermalinkResolveKeys,
  resolvePublicPermalink,
} from '@/shared/lib/public-permalink-resolve'

/**
 * Resolve an arbitrary custom-permalink path → record (#656), so the type-based
 * public SPA router can render custom-permalink pages on direct load and
 * client-side navigation. Disabled for empty / root paths.
 */
export function usePublicPermalinkResolution(
  path: string,
): UseQueryResult<PublicPermalinkResolutionDto> {
  return useQuery({
    queryKey: publicPermalinkResolveKeys.byPath(path),
    queryFn: ({ signal }) => resolvePublicPermalink(path, signal),
    enabled: path !== '' && path !== '/',
  })
}

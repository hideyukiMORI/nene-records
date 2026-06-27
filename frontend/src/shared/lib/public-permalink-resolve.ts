import { apiClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema.gen'

export type PublicPermalinkResolutionDto = components['schemas']['PublicPermalinkResolution']

export const publicPermalinkResolveKeys = {
  byPath: (path: string) => ['public-permalink-resolve', path] as const,
}

/**
 * Resolve an arbitrary custom-permalink path (e.g. `/company/about`) to its
 * record (#656). The public SPA router is type-based and can't resolve such a
 * path on its own; this lets it render the right record on direct load and on
 * client-side navigation. Resolves to `{ found: false }` for unknown paths.
 */
export function resolvePublicPermalink(
  path: string,
  signal?: AbortSignal,
): Promise<PublicPermalinkResolutionDto> {
  return apiClient.get<PublicPermalinkResolutionDto>(
    `/api/v1/public/records/resolve?path=${encodeURIComponent(path)}`,
    signal,
  )
}

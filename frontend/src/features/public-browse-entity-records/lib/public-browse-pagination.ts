export const PUBLIC_BROWSE_PAGE_SIZE = 20

export function parsePublicBrowseOffset(raw: string | null): number {
  const parsed = Number(raw ?? '0')

  if (!Number.isInteger(parsed) || parsed < 0) {
    return 0
  }

  return parsed
}

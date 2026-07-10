/**
 * Site-wide defaults for the public record page's comments section and the
 * "Keep reading / More from {type}" related-records block (#775), stored as
 * JSON in the public `record_page_config` setting. A record's tri-state
 * `show_comments` / `show_related` (null = inherit) overrides these.
 */

export interface RecordPageConfig {
  /** Show the comments section on public record pages. */
  comments: boolean
  /** Show the related-records ("Keep reading") block on public record pages. */
  related: boolean
}

export const DEFAULT_RECORD_PAGE_CONFIG: RecordPageConfig = {
  comments: true,
  related: true,
}

/** Parse the stored `record_page_config` JSON defensively into a full config. */
export function parseRecordPageConfig(raw: string | undefined | null): RecordPageConfig {
  if (raw === undefined || raw === null || raw.trim() === '') {
    return DEFAULT_RECORD_PAGE_CONFIG
  }

  let parsed: unknown
  try {
    parsed = JSON.parse(raw)
  } catch {
    return DEFAULT_RECORD_PAGE_CONFIG
  }
  if (typeof parsed !== 'object' || parsed === null) {
    return DEFAULT_RECORD_PAGE_CONFIG
  }

  const record = parsed as Record<string, unknown>

  return {
    comments: record.comments !== false,
    related: record.related !== false,
  }
}

export function serializeRecordPageConfig(config: RecordPageConfig): string {
  return JSON.stringify(config)
}

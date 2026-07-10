import { useMemo, useState } from 'react'
import { useThemeAuthoringGuide } from '@/entities/theme'
import { useTranslation } from '@/shared/i18n'
import { isSafeTokenValue, TOKEN_KEY } from '@/shared/lib/runtime-themes'
import { Button, Stack, Text } from '@/shared/ui'

/** One documented optional engine token from the authoring guide. */
interface TokenCatalogEntry {
  token: string
  group: string
  drives: string
}

/**
 * Advanced token overrides (#785): the escape hatch that makes the documented
 * optional engine tokens reachable from the UI (no MCP/API). The catalog is the
 * authoring guide's `optionalTokens` — adding an engine token there makes it
 * appear here with no UI work. Values are re-validated at emission; an unsafe
 * value is highlighted and simply never emitted.
 */
export function TokenOverridesEditor({
  tokens,
  disabled,
  onChange,
}: {
  tokens: Record<string, string> | undefined
  disabled: boolean
  onChange: (next: Record<string, string> | undefined) => void
}) {
  const { t } = useTranslation()
  const guideQuery = useThemeAuthoringGuide()
  const [pendingToken, setPendingToken] = useState('')

  const catalog = useMemo((): TokenCatalogEntry[] => {
    const raw = guideQuery.data?.renderModel['optionalTokens']
    if (typeof raw !== 'object' || raw === null) {
      return []
    }
    return Object.entries(raw as Record<string, unknown>).flatMap(
      ([token, doc]): TokenCatalogEntry[] => {
        if (!TOKEN_KEY.test(token)) return []
        const detail =
          typeof doc === 'object' && doc !== null ? (doc as Record<string, unknown>) : {}
        return [
          {
            token,
            group: typeof detail['group'] === 'string' ? detail['group'] : '',
            drives: typeof detail['drives'] === 'string' ? detail['drives'] : '',
          },
        ]
      },
    )
  }, [guideQuery.data])

  const current = tokens ?? {}
  const entries = Object.entries(current)
  const availableByGroup = useMemo(() => {
    const used = tokens ?? {}
    const groups = new Map<string, TokenCatalogEntry[]>()
    for (const entry of catalog) {
      if (entry.token in used) continue
      const list = groups.get(entry.group) ?? []
      list.push(entry)
      groups.set(entry.group, list)
    }
    return groups
  }, [catalog, tokens])

  const emit = (next: Record<string, string>): void => {
    onChange(Object.keys(next).length > 0 ? next : undefined)
  }

  return (
    <Stack gap="xs" className="border-t border-border pt-stack-sm">
      <Text muted variant="caption">
        {t('admin.themeCustomize.tokens.help')}
      </Text>
      {entries.map(([token, value]) => {
        const safe = isSafeTokenValue(value)
        return (
          <div key={token} className="flex items-center gap-inline-sm">
            <span className="w-44 shrink-0 truncate font-mono text-caption text-text-primary">
              --{token}
            </span>
            <input
              type="text"
              className={`min-w-0 flex-1 rounded-sm border bg-surface px-inline-sm py-stack-xs font-mono text-body-sm text-text-primary ${safe ? 'border-border' : 'border-danger'}`}
              value={value}
              disabled={disabled}
              aria-label={`--${token}`}
              aria-invalid={!safe}
              title={safe ? undefined : t('admin.themeCustomize.tokens.invalid')}
              onChange={(event) => {
                emit({ ...current, [token]: event.target.value })
              }}
            />
            <Button
              variant="ghost"
              size="sm"
              disabled={disabled}
              onClick={() => {
                emit(Object.fromEntries(entries.filter(([key]) => key !== token)))
              }}
            >
              {t('admin.themeCustomize.tokens.remove')}
            </Button>
          </div>
        )
      })}
      <div className="flex items-center gap-inline-sm">
        <select
          className="rounded-sm border border-border bg-surface px-inline-sm py-stack-xs font-sans text-body text-text-primary"
          value={pendingToken}
          disabled={disabled}
          aria-label={t('admin.themeCustomize.tokens.placeholder')}
          onChange={(event) => {
            setPendingToken(event.target.value)
          }}
        >
          <option value="">{t('admin.themeCustomize.tokens.placeholder')}</option>
          {[...availableByGroup.entries()].map(([group, list]) => (
            <optgroup key={group} label={group}>
              {list.map((entry) => (
                <option key={entry.token} value={entry.token} title={entry.drives}>
                  --{entry.token}
                </option>
              ))}
            </optgroup>
          ))}
        </select>
        <Button
          variant="secondary"
          size="sm"
          disabled={disabled || pendingToken === ''}
          onClick={() => {
            emit({ ...current, [pendingToken]: '' })
            setPendingToken('')
          }}
        >
          {t('admin.themeCustomize.tokens.add')}
        </Button>
      </div>
    </Stack>
  )
}

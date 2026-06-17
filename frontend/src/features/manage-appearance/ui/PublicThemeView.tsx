import { useTranslation } from '@/shared/i18n'
import type { PublicThemeMeta } from '@/shared/lib/public-themes'
import { Card, Stack, Text } from '@/shared/ui'
import { IconCheck } from '@/shared/ui/icons/Icons'
import type { PublicThemePageState } from '../hooks/usePublicThemePage'

/** Format an ISO (YYYY-MM-DD) date for the active locale; '' if unparseable. */
function formatThemeDate(iso: string, locale: string): string {
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toLocaleDateString(locale, { year: 'numeric', month: 'short', day: 'numeric' })
}

/**
 * Card thumbnail: the theme's preview image when present, otherwise a colour
 * swatch (surface · raised · accent) so every theme still reads at a glance.
 * The image slot is ready ahead of real screenshots (see public-themes.ts).
 */
function ThemeThumb({ theme }: { theme: PublicThemeMeta }) {
  if (theme.thumbnail !== undefined && theme.thumbnail !== '') {
    return (
      <img
        src={theme.thumbnail}
        alt=""
        loading="lazy"
        style={{ aspectRatio: '16 / 7' }}
        className="w-full rounded-sm border border-border object-cover"
      />
    )
  }
  return (
    <span
      aria-hidden
      style={{ aspectRatio: '16 / 7' }}
      className="flex w-full overflow-hidden rounded-sm border border-border"
    >
      <i className="flex-1" style={{ background: theme.preview.surface }} />
      <i className="flex-1" style={{ background: theme.preview.raised }} />
      <i className="flex-1" style={{ background: theme.preview.accent }} />
    </span>
  )
}

/**
 * Admin picker for the public-site theme. WordPress-style cards: a preview
 * thumbnail plus name / description / version · author · date. Writes the
 * `active_theme` public setting — i.e. it controls the visitor-facing site
 * look, not the admin chrome.
 */
export function PublicThemeView({
  themes,
  activeThemeId,
  selectTheme,
  isLoading,
  isSaving,
  pendingThemeId,
}: PublicThemePageState) {
  const { t, locale } = useTranslation()

  return (
    <Stack gap="sm">
      <Text muted variant="caption">
        {t('admin.publicTheme.intro')}
      </Text>
      <Card padding="none" className="p-stack-md">
        <div
          className="grid gap-stack-sm"
          style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))' }}
        >
          {themes.map((theme) => {
            const isSelected = theme.id === activeThemeId
            const date = formatThemeDate(theme.createdAt, locale)
            return (
              <button
                key={theme.id}
                type="button"
                aria-pressed={isSelected}
                disabled={isLoading || isSaving}
                onClick={() => {
                  selectTheme(theme.id)
                }}
                className={[
                  'flex flex-col gap-stack-xs rounded-md border bg-surface p-stack-sm text-left transition-colors duration-fast',
                  'focus-visible:outline-none focus-visible:shadow-focus disabled:opacity-60',
                  isSelected
                    ? 'border-accent ring-1 ring-accent'
                    : 'border-border hover:border-accent',
                ].join(' ')}
              >
                <ThemeThumb theme={theme} />
                <span className="flex items-center gap-inline-xs">
                  <span className="min-w-0 flex-1 truncate font-chrome text-caption font-semibold text-text-primary">
                    {theme.name}
                  </span>
                  {pendingThemeId === theme.id ? (
                    <span className="shrink-0 text-caption text-text-muted">
                      {t('admin.publicTheme.saving')}
                    </span>
                  ) : isSelected ? (
                    <IconCheck size={15} className="shrink-0 text-accent" />
                  ) : null}
                </span>
                <span className="line-clamp-2 text-caption text-text-muted">
                  {theme.description}
                </span>
                <span className="font-chrome text-tiny text-text-muted">
                  {`v${theme.version} · ${theme.author}${date !== '' ? ` · ${date}` : ''}`}
                </span>
              </button>
            )
          })}
        </div>
      </Card>
    </Stack>
  )
}

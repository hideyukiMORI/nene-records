import { useTranslation } from '@/shared/i18n'
import { Card, Stack, Text } from '@/shared/ui'
import { IconCheck } from '@/shared/ui/icons/Icons'
import type { PublicThemePageState } from '../hooks/usePublicThemePage'

/** A 3-colour mini swatch: surface · raised · accent. */
function Swatch({ surface, raised, accent }: { surface: string; raised: string; accent: string }) {
  return (
    <span className="pf-swatch">
      <i style={{ background: surface }} />
      <i style={{ background: raised }} />
      <i style={{ background: accent }} />
    </span>
  )
}

/**
 * Admin picker for the public-site theme. Mirrors AppearanceView (the admin UI
 * theme picker) but writes the `active_theme` public setting — i.e. it controls
 * the visitor-facing site look, not the admin chrome.
 */
export function PublicThemeView({
  themes,
  activeThemeId,
  selectTheme,
  isLoading,
  isSaving,
  pendingThemeId,
}: PublicThemePageState) {
  const { t } = useTranslation()

  return (
    <Stack gap="sm">
      <Text muted variant="caption">
        {t('admin.publicTheme.intro')}
      </Text>
      <Card padding="none" className="p-stack-md">
        <div className="grid grid-cols-1 gap-stack-sm sm:grid-cols-2">
          {themes.map((theme) => {
            const isSelected = theme.id === activeThemeId
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
                  'flex items-center gap-inline-sm rounded-sm border bg-surface px-inline-sm py-stack-xs text-left transition-colors duration-fast',
                  'focus-visible:outline-none focus-visible:shadow-focus disabled:opacity-60',
                  isSelected
                    ? 'border-accent ring-1 ring-accent'
                    : 'border-border hover:border-accent',
                ].join(' ')}
              >
                <Swatch
                  surface={theme.preview.surface}
                  raised={theme.preview.raised}
                  accent={theme.preview.accent}
                />
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
              </button>
            )
          })}
        </div>
      </Card>
    </Stack>
  )
}

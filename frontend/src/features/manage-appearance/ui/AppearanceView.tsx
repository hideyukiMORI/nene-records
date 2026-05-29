import { ADMIN_THEME_DEFS, useTheme, type AdminThemeId, type ThemeVariant } from '@/shared/theme'
import { useTranslation } from '@/shared/i18n'
import { IconCheck } from '@/shared/ui/icons/Icons'

/** A 3-colour mini swatch: sidebar · surface · accent. */
function Swatch({
  sidebar,
  surface,
  accent,
}: {
  sidebar: string
  surface: string
  accent: string
}) {
  return (
    <span className="pf-swatch">
      <i style={{ background: sidebar }} />
      <i style={{ background: surface }} />
      <i style={{ background: accent }} />
    </span>
  )
}

interface ThemeChoice {
  id: AdminThemeId
  variant: ThemeVariant
  label: string
  preview: { surface: string; sidebar: string; accent: string }
}

export function AppearanceView() {
  const { t } = useTranslation()
  const { adminThemeId, themeVariant, setAdminTheme } = useTheme()

  // Flatten themes into one card per (theme × variant): "Ubuntu Light", "Dracula", …
  const choices: ThemeChoice[] = ADMIN_THEME_DEFS.flatMap((def) =>
    def.variants.flatMap((variant) => {
      const preview = def.preview[variant]
      if (preview === undefined) return []
      const label =
        def.variants.length > 1
          ? `${def.name} ${variant === 'light' ? t('admin.theme.light') : t('admin.theme.dark')}`
          : def.name
      return [{ id: def.id, variant, label, preview }]
    }),
  )

  return (
    <div className="rounded-md border border-border bg-surface-raised p-stack-md shadow-sm">
      <div className="grid grid-cols-1 gap-stack-sm sm:grid-cols-2">
        {choices.map((choice) => {
          const isSelected = choice.id === adminThemeId && choice.variant === themeVariant
          return (
            <button
              key={`${choice.id}-${choice.variant}`}
              type="button"
              aria-pressed={isSelected}
              onClick={() => {
                setAdminTheme(choice.id, choice.variant)
              }}
              className={[
                'flex items-center gap-inline-sm rounded-sm border bg-surface px-inline-sm py-stack-xs text-left transition-colors duration-fast',
                'focus-visible:outline-none focus-visible:shadow-focus',
                isSelected
                  ? 'border-accent ring-1 ring-accent'
                  : 'border-border hover:border-accent',
              ].join(' ')}
            >
              <Swatch
                sidebar={choice.preview.sidebar}
                surface={choice.preview.surface}
                accent={choice.preview.accent}
              />
              <span className="min-w-0 flex-1 truncate font-chrome text-caption font-semibold text-text-primary">
                {choice.label}
              </span>
              {isSelected ? <IconCheck size={15} className="shrink-0 text-accent" /> : null}
            </button>
          )
        })}
      </div>
      <p className="mt-stack-sm font-sans text-caption text-text-muted">
        {t('admin.theme.appliesNote')}
      </p>
    </div>
  )
}

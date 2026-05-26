import { useTheme } from '@/shared/theme'
import { ADMIN_THEME_DEFS, type ThemeVariant } from '@/shared/theme'
import { useTranslation } from '@/shared/i18n'
import { Stack, Text } from '@/shared/ui'
import { IconSun, IconMoon } from '@/shared/ui/icons/Icons'

interface ThemePreviewProps {
  surface: string
  sidebar: string
  accent: string
}

function ThemePreview({ surface, sidebar, accent }: ThemePreviewProps) {
  return (
    <div className="flex h-14 overflow-hidden rounded-t-md border-b border-border">
      {/* Sidebar strip */}
      <div className="w-8 shrink-0" style={{ backgroundColor: sidebar }}>
        <div
          className="mx-auto mt-2 h-1 w-4 rounded-full opacity-60"
          style={{ backgroundColor: accent }}
        />
        <div
          className="mx-auto mt-1 h-1 w-4 rounded-full opacity-30"
          style={{ backgroundColor: accent }}
        />
        <div
          className="mx-auto mt-1 h-1 w-4 rounded-full opacity-30"
          style={{ backgroundColor: accent }}
        />
      </div>
      {/* Main area */}
      <div className="flex-1 p-2" style={{ backgroundColor: surface }}>
        <div className="mb-1.5 h-1.5 w-12 rounded-full" style={{ backgroundColor: accent }} />
        <div
          className="mb-1 h-1 w-full rounded-full opacity-20"
          style={{ backgroundColor: sidebar }}
        />
        <div className="h-1 w-3/4 rounded-full opacity-20" style={{ backgroundColor: sidebar }} />
      </div>
    </div>
  )
}

export function AppearanceView() {
  const { t } = useTranslation()
  const { adminThemeId, themeVariant, setAdminTheme } = useTheme()

  return (
    <Stack gap="sm">
      <Text variant="heading-sm">{t('admin.settings.appearance.title')}</Text>
      <div className="grid grid-cols-2 gap-stack-sm sm:grid-cols-3 lg:grid-cols-5">
        {ADMIN_THEME_DEFS.map((def) => {
          const isSelected = def.id === adminThemeId
          // カード表示には現在のバリアントを優先、未選択テーマはデフォルトバリアントのプレビューを表示
          const displayVariant: ThemeVariant = isSelected
            ? themeVariant
            : (def.variants[0] as ThemeVariant)
          const previewColors = def.preview[displayVariant]

          return (
            <div
              key={def.id}
              role="button"
              tabIndex={0}
              aria-pressed={isSelected}
              onClick={() => {
                setAdminTheme(def.id)
              }}
              onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault()
                  setAdminTheme(def.id)
                }
              }}
              className={[
                'cursor-pointer rounded-md border-2 transition-colors duration-fast focus-visible:outline-none focus-visible:shadow-focus',
                isSelected ? 'border-accent' : 'border-border hover:border-accent',
              ].join(' ')}
            >
              {/* カラープレビュー */}
              {previewColors !== undefined ? (
                <ThemePreview
                  surface={previewColors.surface}
                  sidebar={previewColors.sidebar}
                  accent={previewColors.accent}
                />
              ) : null}

              {/* テーマ名 + バリアントトグル */}
              <div className="flex items-center justify-between px-inline-sm py-stack-xs">
                <Text as="span" variant="caption">
                  {def.name}
                </Text>
                {isSelected && def.variants.length > 1 ? (
                  <button
                    type="button"
                    aria-label={
                      themeVariant === 'dark'
                        ? t('admin.theme.toggleLight')
                        : t('admin.theme.toggleDark')
                    }
                    onClick={(e) => {
                      e.stopPropagation()
                      const next: ThemeVariant = themeVariant === 'light' ? 'dark' : 'light'
                      setAdminTheme(def.id, next)
                    }}
                    className="flex items-center justify-center rounded p-0.5 text-text-muted transition-colors hover:text-text-primary"
                  >
                    {themeVariant === 'dark' ? <IconSun size={13} /> : <IconMoon size={13} />}
                  </button>
                ) : null}
              </div>
            </div>
          )
        })}
      </div>
    </Stack>
  )
}

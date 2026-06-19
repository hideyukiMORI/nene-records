import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import type { PublicThemeMeta } from '@/shared/lib/public-themes'
import { Card, ConfirmDialog, Stack, Text, Textarea } from '@/shared/ui'
import { IconCheck } from '@/shared/ui/icons/Icons'
import type { PublicThemePageState } from '../hooks/usePublicThemePage'
import { ThemeMiniPreview } from './ThemeMiniPreview'

/** Format an ISO (YYYY-MM-DD) date for the active locale; '' if unparseable. */
function formatThemeDate(iso: string, locale: string): string {
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toLocaleDateString(locale, { year: 'numeric', month: 'short', day: 'numeric' })
}

/**
 * Card thumbnail: the theme's preview image (`assets.preview`) when present,
 * otherwise a live mini mockup generated from the theme's own swatch tokens —
 * so every runtime theme has a recognisable thumbnail with no image upload
 * (#450). The image stays as an optional override.
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
  return <ThemeMiniPreview preview={theme.preview} />
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
  runtimeThemes,
  runtimeKeys,
  deleteTheme,
  updateTheme,
  isMutating,
}: PublicThemePageState) {
  const { t, locale } = useTranslation()
  const [detailsId, setDetailsId] = useState<string | null>(null)
  const [confirmKey, setConfirmKey] = useState<string | null>(null)
  const [editKey, setEditKey] = useState<string | null>(null)
  const [draft, setDraft] = useState('')
  const [editError, setEditError] = useState<string | null>(null)

  const detailsTheme = themes.find((theme) => theme.id === detailsId)

  const openEdit = (key: string): void => {
    const theme = runtimeThemes.find((item) => item.theme_key === key)
    if (theme === undefined) {
      return
    }
    setDraft(JSON.stringify(theme.manifest, null, 2))
    setEditError(null)
    setEditKey(key)
  }

  const saveEdit = (): void => {
    if (editKey === null) {
      return
    }
    let manifest: unknown
    try {
      manifest = JSON.parse(draft)
    } catch {
      setEditError(t('admin.publicTheme.invalidJson'))
      return
    }
    updateTheme(editKey, manifest as Parameters<typeof updateTheme>[1], {
      onSuccess: () => {
        setEditKey(null)
      },
      onError: setEditError,
    })
  }

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
            const isRuntime = runtimeKeys.has(theme.id)
            return (
              <div key={theme.id} className="flex flex-col gap-stack-xs">
                <button
                  type="button"
                  aria-current={isSelected ? 'true' : undefined}
                  disabled={isLoading || isSaving}
                  onClick={() => {
                    setDetailsId(theme.id)
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
                    {isRuntime ? (
                      <span className="shrink-0 rounded-full border border-border px-inline-xs text-tiny text-text-muted">
                        {t('admin.publicTheme.runtimeBadge')}
                      </span>
                    ) : null}
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
                {isRuntime ? (
                  <span className="flex items-center gap-inline-sm px-inline-xs">
                    <button
                      type="button"
                      className="font-chrome text-tiny text-text-muted hover:text-accent"
                      onClick={() => {
                        openEdit(theme.id)
                      }}
                    >
                      {t('admin.publicTheme.edit')}
                    </button>
                    <button
                      type="button"
                      className="font-chrome text-tiny text-text-muted hover:text-danger"
                      onClick={() => {
                        setConfirmKey(theme.id)
                      }}
                    >
                      {t('admin.publicTheme.delete')}
                    </button>
                  </span>
                ) : null}
              </div>
            )
          })}
        </div>
      </Card>

      <ConfirmDialog
        open={confirmKey !== null}
        title={t('admin.publicTheme.deleteTitle')}
        description={t('admin.publicTheme.deleteBody', { name: confirmKey ?? '' })}
        confirmLabel={t('admin.publicTheme.delete')}
        cancelLabel={t('common.actions.cancel')}
        isPending={isMutating}
        onConfirm={() => {
          if (confirmKey !== null) {
            deleteTheme(confirmKey)
          }
          setConfirmKey(null)
        }}
        onCancel={() => {
          setConfirmKey(null)
        }}
      />

      <ConfirmDialog
        open={editKey !== null}
        title={t('admin.publicTheme.editTitle')}
        description={t('admin.publicTheme.editBody')}
        errorDetail={editError}
        confirmLabel={t('admin.publicTheme.save')}
        cancelLabel={t('common.actions.cancel')}
        isPending={isMutating}
        onConfirm={saveEdit}
        onCancel={() => {
          setEditKey(null)
        }}
      >
        <Textarea
          id="runtime-theme-manifest"
          aria-label={t('admin.publicTheme.editTitle')}
          value={draft}
          onChange={(event) => {
            setDraft(event.target.value)
          }}
          rows={16}
          mono
        />
      </ConfirmDialog>

      {detailsTheme !== undefined ? (
        <ConfirmDialog
          open
          title={detailsTheme.name}
          confirmLabel={
            detailsTheme.id === activeThemeId
              ? t('admin.publicTheme.applied')
              : t('admin.publicTheme.apply')
          }
          cancelLabel={t('common.actions.cancel')}
          confirmDisabled={detailsTheme.id === activeThemeId}
          isPending={pendingThemeId === detailsTheme.id}
          onConfirm={() => {
            selectTheme(detailsTheme.id)
            setDetailsId(null)
          }}
          onCancel={() => {
            setDetailsId(null)
          }}
        >
          <Stack gap="sm">
            <ThemeThumb theme={detailsTheme} />
            <Text variant="caption">{detailsTheme.description}</Text>
            <Text muted variant="caption">
              {`v${detailsTheme.version} · ${detailsTheme.author}${
                formatThemeDate(detailsTheme.createdAt, locale) !== ''
                  ? ` · ${formatThemeDate(detailsTheme.createdAt, locale)}`
                  : ''
              }`}
            </Text>
          </Stack>
        </ConfirmDialog>
      ) : null}
    </Stack>
  )
}

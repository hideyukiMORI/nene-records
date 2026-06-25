import { useState } from 'react'
import { useMediaList } from '@/entities/media'
import type { SettingItem, SettingRevision } from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, ErrorState, LoadingState, Stack, Text, Textarea } from '@/shared/ui'

const inputClass =
  'rounded-md border border-border bg-surface px-inline-sm py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50'

// ── Revisions panel (props-driven) ───────────────────────────────────────────

interface SettingRevisionsPanelProps {
  revisions: SettingRevision[]
  isLoading: boolean
  isError: boolean
}

function SettingRevisionsPanel({ revisions, isLoading, isError }: SettingRevisionsPanelProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.settings.history.loading')}</Text>
  }

  if (isError) {
    return <Text muted>{t('admin.settings.history.error')}</Text>
  }

  if (revisions.length === 0) {
    return <Text muted>{t('admin.settings.history.empty')}</Text>
  }

  return (
    <Stack gap="xs">
      {revisions.map((revision) => (
        <Text key={revision.id} muted variant="caption">
          {revision.createdAt} · {revision.action}
          {revision.previousValue !== null ? ` · from "${revision.previousValue}"` : ''}
        </Text>
      ))}
    </Stack>
  )
}

// ── Media setting field (logo) ───────────────────────────────────────────────

interface MediaSettingFieldProps {
  item: SettingItem
  isSaving: boolean
  canManageSettings: boolean
  onSave: (settingKey: string, value: string) => Promise<void>
}

/**
 * A `media`-type setting stores a media id. Pick from the image library (a
 * preview + a simple select); the public site resolves the id to a URL.
 */
function MediaSettingField({ item, isSaving, canManageSettings, onSave }: MediaSettingFieldProps) {
  const { t } = useTranslation()
  const mediaList = useMediaList()
  const [value, setValue] = useState(item.value)
  const images = (mediaList.data?.items ?? []).filter((m) => m.mimeType.startsWith('image/'))
  const selected = images.find((m) => String(m.id) === value)
  const dirty = value !== item.value

  return (
    <div className="flex items-center gap-stack-sm">
      <span className="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-sm border border-border bg-surface-sunken">
        {selected !== undefined ? (
          <img src={selected.url} alt="" className="h-full w-full object-cover" />
        ) : (
          <Text muted variant="caption">
            —
          </Text>
        )}
      </span>
      <select
        value={value}
        disabled={isSaving || !canManageSettings || mediaList.isLoading}
        onChange={(e) => {
          setValue(e.target.value)
        }}
        className={`flex-1 ${inputClass}`}
      >
        <option value="">{t('admin.settings.media.none')}</option>
        {images.map((m) => (
          <option key={m.id} value={String(m.id)}>
            {m.originalName}
          </option>
        ))}
      </select>
      {canManageSettings ? (
        <Button
          variant="secondary"
          size="sm"
          disabled={isSaving || !dirty}
          onClick={() => {
            void onSave(item.settingKey, value)
          }}
        >
          {isSaving ? t('admin.settings.saving') : t('admin.settings.save')}
        </Button>
      ) : null}
    </div>
  )
}

// ── Consent-default setting field (denied / granted dropdown) ────────────────

interface ConsentDefaultFieldProps {
  item: SettingItem
  isSaving: boolean
  canManageSettings: boolean
  onSave: (settingKey: string, value: string) => Promise<void>
}

/**
 * `analytics_consent_default` is a fixed two-value choice (Consent Mode v2). A
 * select avoids free-text typos; the backend normalizes anything unexpected to
 * `denied`, but the dropdown makes the EU-safe default explicit.
 */
function ConsentDefaultField({
  item,
  isSaving,
  canManageSettings,
  onSave,
}: ConsentDefaultFieldProps) {
  const { t } = useTranslation()
  const initial = item.value === 'granted' ? 'granted' : 'denied'
  const [value, setValue] = useState(initial)
  const dirty = value !== initial

  return (
    <div className="flex items-center gap-stack-sm">
      <select
        id={`setting-${item.settingKey}`}
        value={value}
        disabled={isSaving || !canManageSettings}
        onChange={(e) => {
          setValue(e.target.value)
        }}
        className={`flex-1 ${inputClass}`}
      >
        <option value="denied">{t('admin.settings.consentDefault.denied')}</option>
        <option value="granted">{t('admin.settings.consentDefault.granted')}</option>
      </select>
      {canManageSettings ? (
        <Button
          variant="secondary"
          size="sm"
          disabled={isSaving || !dirty}
          onClick={() => {
            void onSave(item.settingKey, value)
          }}
        >
          {isSaving ? t('admin.settings.saving') : t('admin.settings.save')}
        </Button>
      ) : null}
    </div>
  )
}

// ── Setting card (local form state only) ─────────────────────────────────────

interface SettingCardProps {
  item: SettingItem
  isSaving: boolean
  canManageSettings: boolean
  onSave: (settingKey: string, value: string) => Promise<void>
  isExpanded: boolean
  onToggle: () => void
  revisions: SettingRevision[]
  revisionsLoading: boolean
  revisionsError: boolean
}

function SettingCard({
  item,
  isSaving,
  canManageSettings,
  onSave,
  isExpanded,
  onToggle,
  revisions,
  revisionsLoading,
  revisionsError,
}: SettingCardProps) {
  const { t } = useTranslation()
  const [value, setValue] = useState(item.value)
  const inputId = `setting-${item.settingKey}`
  const dirty = value !== item.value

  return (
    <Card as="section">
      {/* ── Header row: label + history toggle ── */}
      <div className="mb-stack-sm flex items-center justify-between gap-inline-md">
        <label htmlFor={inputId} className="font-sans text-body font-medium text-text-primary">
          {item.label}
        </label>
        <button
          type="button"
          onClick={onToggle}
          className="shrink-0 font-sans text-caption text-text-muted transition-colors hover:text-text-primary"
        >
          {isExpanded ? t('admin.settings.history.hide') : t('admin.settings.history.show')}
        </button>
      </div>

      {/* ── Field ── */}
      {item.dataType === 'media' ? (
        <MediaSettingField
          item={item}
          isSaving={isSaving}
          canManageSettings={canManageSettings}
          onSave={onSave}
        />
      ) : item.dataType === 'markdown' ? (
        <div className="flex flex-col gap-stack-sm">
          <Text muted variant="caption">
            Markdown ·{' '}
            {item.isPublic
              ? t('admin.settings.visibility.public')
              : t('admin.settings.visibility.adminOnly')}
          </Text>
          <Textarea
            id={inputId}
            value={value}
            disabled={isSaving || !canManageSettings}
            rows={4}
            onChange={(e) => {
              setValue(e.target.value)
            }}
            className="w-full resize-y"
          />
          {canManageSettings ? (
            <div className="flex justify-end">
              <Button
                variant="secondary"
                size="sm"
                disabled={isSaving || !dirty}
                onClick={() => {
                  void onSave(item.settingKey, value)
                }}
              >
                {isSaving ? t('admin.settings.saving') : t('admin.settings.save')}
              </Button>
            </div>
          ) : null}
        </div>
      ) : item.settingKey === 'analytics_consent_default' ? (
        <ConsentDefaultField
          item={item}
          isSaving={isSaving}
          canManageSettings={canManageSettings}
          onSave={onSave}
        />
      ) : (
        <div className="flex items-center gap-stack-sm">
          <input
            id={inputId}
            type="text"
            value={value}
            disabled={isSaving || !canManageSettings}
            onChange={(e) => {
              setValue(e.target.value)
            }}
            className={`flex-1 ${inputClass}`}
          />
          {canManageSettings ? (
            <Button
              variant="secondary"
              size="sm"
              disabled={isSaving || !dirty}
              onClick={() => {
                void onSave(item.settingKey, value)
              }}
            >
              {isSaving ? t('admin.settings.saving') : t('admin.settings.save')}
            </Button>
          ) : null}
        </div>
      )}

      {/* ── History panel ── */}
      {isExpanded ? (
        <div className="mt-stack-md border-t border-border pt-stack-sm">
          <SettingRevisionsPanel
            revisions={revisions}
            isLoading={revisionsLoading}
            isError={revisionsError}
          />
        </div>
      ) : null}
    </Card>
  )
}

// ── Main view ─────────────────────────────────────────────────────────────────

interface ManageSiteSettingsViewProps {
  items: SettingItem[]
  isLoading: boolean
  isError: boolean
  isSaving: boolean
  expandedKey: string | null
  revisions: SettingRevision[]
  revisionsLoading: boolean
  revisionsError: boolean
  canManageSettings: boolean
  onRetry: () => void
  onSave: (settingKey: string, value: string) => Promise<void>
  onToggleExpanded: (settingKey: string) => void
}

export function ManageSiteSettingsView({
  items,
  isLoading,
  isError,
  isSaving,
  expandedKey,
  revisions,
  revisionsLoading,
  revisionsError,
  canManageSettings,
  onRetry,
  onSave,
  onToggleExpanded,
}: ManageSiteSettingsViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <LoadingState>{t('admin.settings.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        message={t('admin.settings.error')}
        onRetry={onRetry}
        retryLabel={t('common.actions.retry')}
      />
    )
  }

  return (
    <Stack gap="md">
      {items.map((item) => (
        <SettingCard
          key={`${item.settingKey}:${item.updatedAt ?? 'default'}`}
          item={item}
          isSaving={isSaving}
          canManageSettings={canManageSettings}
          onSave={onSave}
          isExpanded={expandedKey === item.settingKey}
          onToggle={() => {
            onToggleExpanded(item.settingKey)
          }}
          revisions={expandedKey === item.settingKey ? revisions : []}
          revisionsLoading={expandedKey === item.settingKey && revisionsLoading}
          revisionsError={expandedKey === item.settingKey && revisionsError}
        />
      ))}
    </Stack>
  )
}

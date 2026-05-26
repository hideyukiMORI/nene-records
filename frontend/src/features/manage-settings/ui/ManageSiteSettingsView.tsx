import { useCallback, useMemo, useState } from 'react'
import {
  useSettingList,
  useSettingRevisions,
  useUpdateSetting,
  type SettingItem,
} from '@/entities/setting'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text } from '@/shared/ui'

function SettingRevisionsPanel({ settingKey }: { settingKey: string }) {
  const { t } = useTranslation()
  const revisionsQuery = useSettingRevisions(settingKey)

  if (revisionsQuery.isLoading) {
    return <Text muted>{t('admin.settings.history.loading')}</Text>
  }

  if (revisionsQuery.isError) {
    return <Text muted>{t('admin.settings.history.error')}</Text>
  }

  const items = revisionsQuery.data?.items ?? []

  if (items.length === 0) {
    return <Text muted>{t('admin.settings.history.empty')}</Text>
  }

  return (
    <Stack gap="xs">
      {items.map((revision) => (
        <Text key={revision.id} muted variant="caption">
          {revision.createdAt} · {revision.action}
          {revision.previousValue !== null ? ` · from "${revision.previousValue}"` : ''}
        </Text>
      ))}
    </Stack>
  )
}

function SettingCard({
  item,
  isSaving,
  canManageSettings,
  onSave,
  isExpanded,
  onToggle,
}: {
  item: SettingItem
  isSaving: boolean
  canManageSettings: boolean
  onSave: (settingKey: string, value: string) => Promise<void>
  isExpanded: boolean
  onToggle: () => void
}) {
  const { t } = useTranslation()
  const [value, setValue] = useState(item.value)
  const inputId = `setting-${item.settingKey}`
  const dirty = value !== item.value

  const inputClass =
    'rounded-md border border-border bg-surface px-inline-sm py-stack-sm font-sans text-body text-text-primary shadow-sm focus-visible:outline-none focus-visible:shadow-focus disabled:cursor-not-allowed disabled:opacity-50'

  return (
    <section className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm">
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
      {item.dataType === 'markdown' ? (
        /* Markdown: textarea stacked, save right-aligned below */
        <div className="flex flex-col gap-stack-sm">
          <Text muted variant="caption">
            Markdown ·{' '}
            {item.isPublic
              ? t('admin.settings.visibility.public')
              : t('admin.settings.visibility.adminOnly')}
          </Text>
          <textarea
            id={inputId}
            value={value}
            disabled={isSaving || !canManageSettings}
            rows={4}
            onChange={(e) => {
              setValue(e.target.value)
            }}
            className={`w-full resize-y ${inputClass}`}
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
      ) : (
        /* Text: input + save button inline */
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
          <SettingRevisionsPanel settingKey={item.settingKey} />
        </div>
      ) : null}
    </section>
  )
}

export function ManageSiteSettingsView({ canManageSettings }: { canManageSettings: boolean }) {
  const { t } = useTranslation()
  const listQuery = useSettingList()
  const updateMutation = useUpdateSetting()
  const [expandedKey, setExpandedKey] = useState<string | null>(null)

  const saveSetting = useCallback(
    async (settingKey: string, value: string) => {
      await updateMutation.mutateAsync({ settingKey, input: { value } })
    },
    [updateMutation],
  )

  const items = useMemo(() => listQuery.data?.items ?? [], [listQuery.data?.items])

  if (listQuery.isLoading) {
    return <Text muted>{t('admin.settings.loading')}</Text>
  }

  if (listQuery.isError) {
    return (
      <Stack gap="sm">
        <Text muted>{t('admin.settings.error')}</Text>
        <Button variant="secondary" size="sm" onClick={() => void listQuery.refetch()}>
          {t('common.actions.retry')}
        </Button>
      </Stack>
    )
  }

  return (
    <Stack gap="md">
      {items.map((item) => (
        <SettingCard
          key={`${item.settingKey}:${item.updatedAt ?? 'default'}`}
          item={item}
          isSaving={updateMutation.isPending}
          canManageSettings={canManageSettings}
          onSave={saveSetting}
          isExpanded={expandedKey === item.settingKey}
          onToggle={() => {
            setExpandedKey((cur) => (cur === item.settingKey ? null : item.settingKey))
          }}
        />
      ))}
    </Stack>
  )
}

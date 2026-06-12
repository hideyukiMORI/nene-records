import { useState } from 'react'
import { useUpdateEntityType, type EntityType } from '@/entities/entity-type'
import { useTranslation } from '@/shared/i18n'
import {
  DEFAULT_PERMALINK_PATTERN,
  PERMALINK_PRESETS,
  resolvePermalink,
} from '@/shared/lib/resolve-permalink'
import { Button, Card, Stack, Text } from '@/shared/ui'

// ── Warning for patterns without {type} ──────────────────────────────────────

function NoTypeTokenWarning({ pattern }: { pattern: string }) {
  const { t } = useTranslation()
  if (!pattern || pattern.includes('{type}')) return null
  return (
    <p className="mt-1 text-xs text-warn">
      ⚠ {t('admin.entityTypes.editForm.permalink.noTypeWarning')}
    </p>
  )
}

// ── Helper: match value to preset or null (= custom) ─────────────────────────

function matchPreset(value: string | null | undefined): string | null {
  if (!value) return DEFAULT_PERMALINK_PATTERN
  return PERMALINK_PRESETS.find((p) => p.pattern === value)?.pattern ?? null
}

// ── Per-entity-type permalink row (per-row mutation kept internal) ─────────────

function PermalinkRow({ entityType, onSaved }: { entityType: EntityType; onSaved: () => void }) {
  const { t } = useTranslation()
  const updateMutation = useUpdateEntityType()

  const [pattern, setPattern] = useState<string>(
    entityType.permalinkPattern ?? DEFAULT_PERMALINK_PATTERN,
  )
  const [isDirty, setIsDirty] = useState(false)

  const exampleCtx = {
    typeSlug: entityType.slug,
    entitySlug: 'my-article',
    entityId: 42,
    publishedAt: '2024-03-15T00:00:00Z',
  }

  const matchedPreset = matchPreset(pattern)
  const isCustom = matchedPreset === null
  const effectivePattern = isCustom ? pattern : matchedPreset

  function handleRadioChange(value: string) {
    setPattern(value)
    setIsDirty(true)
  }

  async function handleSave() {
    const permalinkPattern = pattern === '' ? null : pattern
    await updateMutation.mutateAsync({
      id: entityType.id,
      input: {
        name: entityType.name,
        slug: entityType.slug,
        isPinned: entityType.isPinned,
        labels: entityType.labels,
        permalinkPattern,
      },
    })
    setIsDirty(false)
    onSaved()
  }

  return (
    <Card>
      <Stack gap="sm">
        <div className="flex items-center justify-between">
          <Text variant="heading-sm">{entityType.name}</Text>
          <code className="font-mono text-xs text-text-muted">/{entityType.slug}</code>
        </div>

        <div className="space-y-1">
          {PERMALINK_PRESETS.map((preset) => (
            <label
              key={preset.id}
              htmlFor={`permalink-settings-${String(entityType.id)}-${preset.id}`}
              aria-label={preset.label}
              className="flex cursor-pointer items-start gap-3 rounded p-1.5 hover:bg-surface"
            >
              <input
                id={`permalink-settings-${String(entityType.id)}-${preset.id}`}
                type="radio"
                name={`permalink-${String(entityType.id)}`}
                checked={!isCustom && matchedPreset === preset.pattern}
                disabled={updateMutation.isPending}
                onChange={() => {
                  handleRadioChange(preset.pattern)
                }}
                className="mt-0.5 h-4 w-4 shrink-0 accent-accent"
              />
              <span className="flex flex-col gap-0">
                <span className="text-sm font-medium text-text-primary">{preset.label}</span>
                <code className="font-mono text-xs text-text-muted">
                  {resolvePermalink(preset.pattern, exampleCtx)}
                </code>
              </span>
            </label>
          ))}

          {/* Custom option */}
          <label
            htmlFor={`permalink-settings-${String(entityType.id)}-custom`}
            className="flex cursor-pointer items-start gap-3 rounded p-1.5 hover:bg-surface"
          >
            <input
              id={`permalink-settings-${String(entityType.id)}-custom`}
              type="radio"
              name={`permalink-${String(entityType.id)}`}
              checked={isCustom}
              disabled={updateMutation.isPending}
              onChange={() => {
                handleRadioChange('')
              }}
              className="mt-0.5 h-4 w-4 shrink-0 accent-accent"
            />
            <span className="text-sm font-medium text-text-primary">
              {t('admin.entityTypes.editForm.permalink.custom')}
            </span>
          </label>
        </div>

        {isCustom && (
          <div className="ml-7">
            <input
              type="text"
              value={pattern}
              placeholder={t('admin.entityTypes.editForm.permalink.customPlaceholder')}
              disabled={updateMutation.isPending}
              onChange={(e) => {
                setPattern(e.target.value)
                setIsDirty(true)
              }}
              className="w-full rounded-md border border-border bg-surface-raised px-3 py-1.5 font-mono text-sm text-text-primary focus-visible:outline-none focus-visible:shadow-focus"
            />
            <p className="mt-1 text-xs text-text-muted">
              {t('admin.entityTypes.editForm.permalink.customHelp')}
            </p>
          </div>
        )}

        {effectivePattern && <NoTypeTokenWarning pattern={effectivePattern} />}

        {effectivePattern && !isCustom && (
          <p className="text-xs text-text-muted">
            {t('admin.entityTypes.editForm.permalink.example', {
              example: resolvePermalink(effectivePattern, exampleCtx),
            })}
          </p>
        )}

        {updateMutation.error !== null && <Text muted>{updateMutation.error.title}</Text>}

        {isDirty && (
          <div className="flex gap-inline-sm">
            <Button
              size="sm"
              disabled={updateMutation.isPending}
              onClick={() => {
                void handleSave()
              }}
            >
              {updateMutation.isPending ? t('common.actions.saving') : t('common.actions.save')}
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={updateMutation.isPending}
              onClick={() => {
                setPattern(entityType.permalinkPattern ?? DEFAULT_PERMALINK_PATTERN)
                setIsDirty(false)
                updateMutation.reset()
              }}
            >
              {t('common.actions.cancel')}
            </Button>
          </div>
        )}
      </Stack>
    </Card>
  )
}

// ── Main view (props-driven) ──────────────────────────────────────────────────

interface PermalinkSettingsViewProps {
  entityTypes: EntityType[]
  isLoading: boolean
  onRefresh: () => void
}

export function PermalinkSettingsView({
  entityTypes,
  isLoading,
  onRefresh,
}: PermalinkSettingsViewProps) {
  const { t } = useTranslation()

  return (
    <Stack gap="sm">
      {isLoading && <Text muted>{t('admin.entityTypes.existingList.loading')}</Text>}

      {entityTypes.length === 0 && !isLoading && (
        <Text muted>{t('admin.entityTypes.existingList.empty.description')}</Text>
      )}

      {entityTypes.map((entityType) => (
        <PermalinkRow key={String(entityType.id)} entityType={entityType} onSaved={onRefresh} />
      ))}
    </Stack>
  )
}

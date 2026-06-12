import { Controller } from 'react-hook-form'
import type { EntityType } from '@/entities/entity-type'
import { useTranslation } from '@/shared/i18n'
import {
  DEFAULT_PERMALINK_PATTERN,
  PERMALINK_PRESETS,
  resolvePermalink,
} from '@/shared/lib/resolve-permalink'
import { Button, Card, Input, Select, Stack, Text } from '@/shared/ui'
import {
  EDIT_LABEL_FIELDS,
  useEditEntityTypeForm,
  type EditEntityTypeFormValues,
} from '../hooks/use-create-entity-type-form'

export interface EntityTypeEditFormProps {
  entityType: EntityType
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: EditEntityTypeFormValues) => Promise<void>
  onCancel: () => void
}

/** Returns the preset pattern if the value matches one; otherwise undefined (= custom). */
function matchPreset(value: string | null | undefined) {
  if (!value) return DEFAULT_PERMALINK_PATTERN
  return PERMALINK_PRESETS.find((p) => p.pattern === value)?.pattern ?? null
}

function PermalinkFieldset({
  control,
  isSubmitting,
  entityTypeSlug,
}: {
  control: ReturnType<typeof useEditEntityTypeForm>['control']
  isSubmitting: boolean
  entityTypeSlug: string
}) {
  const { t } = useTranslation()

  const exampleCtx = {
    typeSlug: entityTypeSlug,
    entitySlug: 'my-article',
    entityId: 42,
    publishedAt: '2024-03-15T00:00:00Z',
  }

  return (
    <Controller
      name="permalinkPattern"
      control={control}
      render={({ field }) => {
        // "custom" when the current value doesn't match any preset
        const matchedPreset = matchPreset(field.value)
        const isCustom = matchedPreset === null

        // Live example — matchedPreset is always a string here (non-custom branch)
        const effectivePattern = isCustom ? (field.value ?? '') : matchedPreset
        const liveExample = effectivePattern ? resolvePermalink(effectivePattern, exampleCtx) : null

        return (
          <fieldset className="space-y-3 rounded-md border border-border p-4">
            <legend className="px-1 text-xs font-semibold uppercase tracking-wider text-text-muted">
              {t('admin.entityTypes.editForm.permalink.title')}
            </legend>
            <p className="text-xs text-text-muted">
              {t('admin.entityTypes.editForm.permalink.description')}
            </p>

            <div className="space-y-2">
              {PERMALINK_PRESETS.map((preset) => (
                <label
                  key={preset.id}
                  htmlFor={`permalink-preset-${preset.id}`}
                  aria-label={preset.label}
                  className="flex cursor-pointer items-start gap-3 rounded-md border border-transparent p-2 hover:bg-surface-raised"
                >
                  <input
                    id={`permalink-preset-${preset.id}`}
                    type="radio"
                    name="permalinkPattern-radio"
                    checked={!isCustom && matchedPreset === preset.pattern}
                    disabled={isSubmitting}
                    onChange={() => {
                      field.onChange(preset.pattern)
                    }}
                    className="mt-0.5 h-4 w-4 shrink-0 accent-accent"
                  />
                  <span className="flex flex-col gap-0.5">
                    <span className="text-sm font-medium text-text-primary">{preset.label}</span>
                    <code className="font-mono text-xs text-text-muted">
                      {resolvePermalink(preset.pattern, exampleCtx)}
                    </code>
                  </span>
                </label>
              ))}

              {/* Custom option */}
              <label
                htmlFor="permalink-preset-custom"
                className="flex cursor-pointer items-start gap-3 rounded-md border border-transparent p-2 hover:bg-surface-raised"
              >
                <input
                  id="permalink-preset-custom"
                  type="radio"
                  name="permalinkPattern-radio"
                  checked={isCustom}
                  disabled={isSubmitting}
                  onChange={() => {
                    // When switching to custom, start with empty string so user can type
                    field.onChange('')
                  }}
                  className="mt-0.5 h-4 w-4 shrink-0 accent-accent"
                />
                <span className="text-sm font-medium text-text-primary">
                  {t('admin.entityTypes.editForm.permalink.custom')}
                </span>
              </label>
            </div>

            {isCustom && (
              <div className="ml-7 space-y-1">
                <Input
                  id="entity-type-edit-permalink-custom"
                  label={t('admin.entityTypes.editForm.permalink.custom')}
                  placeholder={t('admin.entityTypes.editForm.permalink.customPlaceholder')}
                  autoComplete="off"
                  disabled={isSubmitting}
                  value={field.value ?? ''}
                  onChange={(e) => {
                    field.onChange(e.target.value)
                  }}
                  onBlur={field.onBlur}
                />
                <p className="text-xs text-text-muted">
                  {t('admin.entityTypes.editForm.permalink.customHelp')}
                </p>
              </div>
            )}

            {liveExample !== null && !isCustom && (
              <p className="text-xs text-text-muted">
                {t('admin.entityTypes.editForm.permalink.example', { example: liveExample })}
              </p>
            )}

            {/* ⚠ No {type} warning */}
            {effectivePattern && !effectivePattern.includes('{type}') && (
              <p className="text-xs text-warning">
                ⚠ {t('admin.entityTypes.editForm.permalink.noTypeWarning')}
              </p>
            )}
          </fieldset>
        )
      }}
    />
  )
}

export function EntityTypeEditForm({
  entityType,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
  onCancel,
}: EntityTypeEditFormProps) {
  const { t } = useTranslation()

  const {
    control,
    handleSubmit,
    formState: { errors },
  } = useEditEntityTypeForm({
    name: entityType.name,
    slug: entityType.slug,
    isPinned: entityType.isPinned,
    labelJa: entityType.labels?.['ja'] ?? '',
    labelFr: entityType.labels?.['fr'] ?? '',
    labelZhHans: entityType.labels?.['zh-Hans'] ?? '',
    labelPtBr: entityType.labels?.['pt-BR'] ?? '',
    labelDe: entityType.labels?.['de'] ?? '',
    // null/undefined → store null; will default to DEFAULT_PERMALINK_PATTERN in the UI
    permalinkPattern: entityType.permalinkPattern ?? null,
    defaultLayout: entityType.defaultLayout,
  })

  return (
    <Card
      as="form"
      key={String(entityType.id)}
      onSubmit={(event) => {
        void handleSubmit(async (values) => {
          await onSubmit(values)
        })(event)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.entityTypes.editForm.title')}
        </Text>
        <Controller
          name="name"
          control={control}
          render={({ field }) => (
            <Input
              id="entity-type-edit-name"
              label={t('common.field.name')}
              error={errors.name?.message}
              autoComplete="off"
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            />
          )}
        />
        <Controller
          name="slug"
          control={control}
          render={({ field }) => (
            <Input
              id="entity-type-edit-slug"
              label={t('common.field.slug')}
              error={errors.slug?.message}
              autoComplete="off"
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            />
          )}
        />
        <Controller
          name="isPinned"
          control={control}
          render={({ field }) => (
            <label
              htmlFor="entity-type-edit-is-pinned"
              aria-label={t('admin.entityTypes.editForm.isPinned')}
              className="flex cursor-pointer items-start gap-3"
            >
              <input
                type="checkbox"
                id="entity-type-edit-is-pinned"
                checked={field.value}
                onChange={field.onChange}
                disabled={isSubmitting}
                className="mt-0.5 h-4 w-4 shrink-0 accent-accent"
              />
              <span className="flex flex-col gap-0.5">
                <span className="text-sm font-medium text-text-primary">
                  {t('admin.entityTypes.editForm.isPinned')}
                </span>
                <span className="text-xs text-text-muted">
                  {t('admin.entityTypes.editForm.isPinnedDescription')}
                </span>
              </span>
            </label>
          )}
        />

        {/* ── Permalink structure ── */}
        <PermalinkFieldset
          control={control}
          isSubmitting={isSubmitting}
          entityTypeSlug={entityType.slug}
        />

        {/* ── Default public-page layout ── */}
        <Controller
          name="defaultLayout"
          control={control}
          render={({ field }) => (
            <Select
              id="entity-type-edit-default-layout"
              label={t('admin.layout.defaultLayout')}
              disabled={isSubmitting}
              value={field.value}
              onChange={field.onChange}
              onBlur={field.onBlur}
            >
              <option value="standard">{t('admin.layout.standard')}</option>
              <option value="full">{t('admin.layout.full')}</option>
              <option value="bare">{t('admin.layout.bare')}</option>
            </Select>
          )}
        />

        {/* ── Display names by language ── */}
        <fieldset className="space-y-3 rounded-md border border-border p-4">
          <legend className="px-1 text-xs font-semibold uppercase tracking-wider text-text-muted">
            {t('admin.entityTypes.editForm.labels.title')}
          </legend>
          <p className="text-xs text-text-muted">
            {t('admin.entityTypes.editForm.labels.description')}
          </p>
          {EDIT_LABEL_FIELDS.map(({ fieldName, nativeLabel }) => (
            <Controller
              key={fieldName}
              name={fieldName}
              control={control}
              render={({ field }) => (
                <Input
                  id={`entity-type-edit-label-${fieldName}`}
                  label={nativeLabel}
                  autoComplete="off"
                  disabled={isSubmitting}
                  value={field.value ?? ''}
                  onChange={field.onChange}
                  onBlur={field.onBlur}
                />
              )}
            />
          ))}
        </fieldset>

        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <div className="flex items-center gap-inline-sm">
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting
              ? t('admin.entityTypes.editForm.saving')
              : t('admin.entityTypes.editForm.save')}
          </Button>
          <Button type="button" variant="secondary" disabled={isSubmitting} onClick={onCancel}>
            {t('common.actions.cancel')}
          </Button>
        </div>
      </Stack>
    </Card>
  )
}

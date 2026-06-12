import type { EntityStatus } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import type { MessageKey } from '@/shared/i18n'
import type { PublicLayoutKey } from '@/shared/lib/resolve-layout'
import { Button, Input, Select, Stack, StatusBadge, Text } from '@/shared/ui'
import type { EntityStatusPanelState } from '../hooks/useEntityStatusPanel'

const NEXT_STATUSES: Record<EntityStatus, EntityStatus[]> = {
  draft: ['published', 'archived'],
  published: ['draft', 'archived'],
  archived: ['draft', 'published'],
  scheduled: ['published', 'draft'],
}

const STATUS_LABEL_KEYS: Record<EntityStatus, MessageKey> = {
  draft: 'admin.entityStatus.status.draft',
  published: 'admin.entityStatus.status.published',
  archived: 'admin.entityStatus.status.archived',
  scheduled: 'admin.entityStatus.status.scheduled',
}

export function EntityStatusPanel({
  entity,
  entityTypeSlug,
  slugInput,
  showScheduleForm,
  scheduledAtInput,
  previewUrl,
  previewExpires,
  isPending,
  layout,
  onSlugInputChange,
  onScheduledAtChange,
  onToggleScheduleForm,
  onCancelScheduleForm,
  onChangeStatus,
  onChangeLayout,
  onSaveSlug,
  onSchedulePublish,
  onCancelSchedule,
  onGeneratePreview,
  onRevokePreview,
}: EntityStatusPanelState) {
  const { t } = useTranslation()

  const currentStatus = entity.status
  const publicUrl =
    entityTypeSlug !== undefined && (entity.slug ?? slugInput) !== ''
      ? `/${entityTypeSlug}/${entity.slug ?? slugInput}`
      : null

  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        {t('admin.entityStatus.panelTitle')}
      </Text>
      <div className="flex flex-wrap items-center gap-inline-md">
        <StatusBadge status={currentStatus}>{t(STATUS_LABEL_KEYS[currentStatus])}</StatusBadge>
        {entity.publishedAt !== null && (
          <Text as="span" muted>
            {t('admin.entityStatus.publishedAt', {
              date: new Date(entity.publishedAt).toLocaleDateString(),
            })}
          </Text>
        )}
        {entity.scheduledAt !== null && (
          <Text as="span" muted>
            {t('admin.entityStatus.scheduledAt', {
              date: new Date(entity.scheduledAt).toLocaleString(),
            })}
          </Text>
        )}
      </div>

      <Stack gap="xs">
        <label htmlFor="entity-slug" className="text-sm font-medium text-text-primary">
          {t('admin.entityStatus.slugLabel')}
        </label>
        <div className="flex items-center gap-inline-sm">
          <Input
            id="entity-slug"
            value={slugInput}
            onChange={(e) => {
              onSlugInputChange(e.target.value)
            }}
            placeholder={t('admin.entityStatus.slugPlaceholder')}
          />
          <Button variant="secondary" size="sm" onClick={onSaveSlug}>
            {t('admin.entityStatus.saveSlug')}
          </Button>
        </div>
        {publicUrl !== null && (
          <a
            href={publicUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm text-accent underline hover:text-accent-hover"
          >
            {publicUrl}
          </a>
        )}
      </Stack>

      <Stack gap="xs">
        <Select
          id="entity-layout"
          label={t('admin.layout.entityLabel')}
          disabled={isPending}
          value={layout ?? ''}
          onChange={(e) => {
            const value = e.target.value
            onChangeLayout(value === '' ? null : (value as PublicLayoutKey))
          }}
        >
          <option value="">{t('admin.layout.inherit')}</option>
          <option value="standard">{t('admin.layout.standard')}</option>
          <option value="full">{t('admin.layout.full')}</option>
          <option value="two-col">{t('admin.layout.twoCol')}</option>
          <option value="three-col">{t('admin.layout.threeCol')}</option>
          <option value="bare">{t('admin.layout.bare')}</option>
        </Select>
      </Stack>

      <div className="flex flex-wrap gap-inline-sm">
        {NEXT_STATUSES[currentStatus].map((nextStatus) => (
          <Button
            key={nextStatus}
            variant="secondary"
            size="sm"
            disabled={isPending}
            onClick={() => {
              onChangeStatus(nextStatus)
            }}
          >
            {nextStatus === 'published'
              ? t('admin.entityStatus.publish')
              : t(STATUS_LABEL_KEYS[nextStatus])}
          </Button>
        ))}
        {currentStatus !== 'scheduled' && (
          <Button variant="secondary" size="sm" disabled={isPending} onClick={onToggleScheduleForm}>
            {t('admin.entityStatus.schedule')}
          </Button>
        )}
        {currentStatus === 'scheduled' && (
          <Button variant="secondary" size="sm" disabled={isPending} onClick={onCancelSchedule}>
            {t('admin.entityStatus.cancelSchedule')}
          </Button>
        )}
        <Button variant="secondary" size="sm" disabled={isPending} onClick={onGeneratePreview}>
          {t('admin.entityStatus.generatePreviewToken')}
        </Button>
      </div>

      {showScheduleForm && (
        <div className="flex items-center gap-inline-sm rounded-md border border-border bg-surface p-3">
          <input
            type="datetime-local"
            value={scheduledAtInput}
            onChange={(e) => {
              onScheduledAtChange(e.target.value)
            }}
            className="rounded-sm border border-border bg-surface px-3 py-2 text-sm text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
          />
          <Button
            size="sm"
            disabled={isPending || scheduledAtInput === ''}
            onClick={onSchedulePublish}
          >
            {t('admin.entityStatus.confirmSchedule')}
          </Button>
          <Button variant="secondary" size="sm" onClick={onCancelScheduleForm}>
            {t('common.actions.cancel')}
          </Button>
        </div>
      )}

      {previewUrl !== null && (
        <div className="rounded-md border border-border bg-surface p-3">
          <Stack gap="xs">
            <Text as="span" muted>
              {t('admin.entityStatus.previewTokenExpires', {
                date: previewExpires !== null ? new Date(previewExpires).toLocaleString() : '',
              })}
            </Text>
            <div className="flex items-center gap-inline-sm">
              <a
                href={previewUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex-1 truncate text-sm text-accent underline hover:text-accent-hover"
              >
                {previewUrl}
              </a>
              <Button variant="secondary" size="sm" disabled={isPending} onClick={onRevokePreview}>
                {t('admin.entityStatus.revokePreviewToken')}
              </Button>
            </div>
          </Stack>
        </div>
      )}
    </Stack>
  )
}

import type { CreateWebhookInput, Webhook, WebhookList } from '@/entities/webhook'
import { useTranslation } from '@/shared/i18n'
import { Button, ConfirmDialog, EmptyState, Stack, Text } from '@/shared/ui'
import { WebhookForm } from './WebhookForm'

interface ManageWebhooksViewProps {
  webhooks: WebhookList | undefined
  isLoading: boolean
  isError: boolean
  showCreateForm: boolean
  editingId: number | null
  deleteTarget: Webhook | null
  isCreating: boolean
  isUpdating: boolean
  isDeleting: boolean
  createError: string | null
  updateError: string | null
  onCreate: (input: CreateWebhookInput) => Promise<void>
  onUpdate: (id: number, input: CreateWebhookInput) => Promise<void>
  onDeleteConfirm: () => Promise<void>
  onShowCreateForm: () => void
  onHideCreateForm: () => void
  onStartEdit: (id: number) => void
  onCancelEdit: () => void
  onDeleteRequest: (webhook: Webhook) => void
  onDeleteCancel: () => void
}

export function ManageWebhooksView({
  webhooks,
  isLoading,
  isError,
  showCreateForm,
  editingId,
  deleteTarget,
  isCreating,
  isUpdating,
  isDeleting,
  createError,
  updateError,
  onCreate,
  onUpdate,
  onDeleteConfirm,
  onShowCreateForm,
  onHideCreateForm,
  onStartEdit,
  onCancelEdit,
  onDeleteRequest,
  onDeleteCancel,
}: ManageWebhooksViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.webhooks.loading')}</Text>
  }

  if (isError || webhooks === undefined) {
    return <Text muted>{t('common.error.serverError')}</Text>
  }

  return (
    <Stack gap="lg">
      <div className="flex items-center justify-between gap-4">
        <Text as="h1" variant="heading-md">
          {t('admin.webhooks.title')}
        </Text>
        {!showCreateForm && (
          <Button type="button" size="sm" onClick={onShowCreateForm}>
            {t('admin.webhooks.addButton')}
          </Button>
        )}
      </div>

      <Text muted>{t('admin.webhooks.description')}</Text>

      {showCreateForm && (
        <WebhookForm
          isSubmitting={isCreating}
          serverErrorTitle={createError}
          submitLabel={t('common.actions.create')}
          onSubmit={onCreate}
          onCancel={onHideCreateForm}
        />
      )}

      {webhooks.items.length === 0 && !showCreateForm ? (
        <EmptyState
          title={t('admin.webhooks.empty.title')}
          description={t('admin.webhooks.empty.description')}
        />
      ) : (
        <Stack gap="sm">
          {webhooks.items.map((webhook) =>
            editingId === webhook.id ? (
              <WebhookForm
                key={webhook.id}
                defaultValues={{
                  url: webhook.url,
                  events: webhook.events,
                  entityTypeId: webhook.entityTypeId,
                  secret: webhook.secret ?? '',
                  isActive: webhook.isActive,
                }}
                isSubmitting={isUpdating}
                serverErrorTitle={updateError}
                submitLabel={t('common.actions.save')}
                onSubmit={async (input) => onUpdate(webhook.id, input)}
                onCancel={onCancelEdit}
              />
            ) : (
              <div
                key={webhook.id}
                className="flex flex-col gap-2 rounded-md border border-border bg-surface-raised p-inline-md shadow-sm sm:flex-row sm:items-start sm:justify-between"
              >
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <span
                      className={`inline-block rounded px-1.5 py-0.5 font-sans text-caption font-medium ${
                        webhook.isActive
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                          : 'bg-surface text-text-muted'
                      }`}
                    >
                      {webhook.isActive
                        ? t('admin.webhooks.status.active')
                        : t('admin.webhooks.status.inactive')}
                    </span>
                    <a
                      href={webhook.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="truncate font-sans text-body text-accent hover:underline"
                    >
                      {webhook.url}
                    </a>
                  </div>
                  <div className="mt-1 flex flex-wrap gap-1">
                    {webhook.events.map((event) => (
                      <code
                        key={event}
                        className="rounded bg-surface px-1.5 py-0.5 font-sans text-caption text-text-muted"
                      >
                        {event}
                      </code>
                    ))}
                    {webhook.entityTypeId !== null && (
                      <span className="font-sans text-caption text-text-muted">
                        {t('admin.webhooks.entityTypeFilter', { id: webhook.entityTypeId })}
                      </span>
                    )}
                  </div>
                </div>
                <div className="flex shrink-0 gap-2">
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      onStartEdit(webhook.id)
                    }}
                  >
                    {t('common.actions.edit')}
                  </Button>
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      onDeleteRequest(webhook)
                    }}
                  >
                    {t('common.actions.delete')}
                  </Button>
                </div>
              </div>
            ),
          )}
        </Stack>
      )}

      <ConfirmDialog
        open={deleteTarget !== null}
        title={t('admin.webhooks.delete.title')}
        description={
          deleteTarget !== null
            ? t('admin.webhooks.delete.description', { url: deleteTarget.url })
            : undefined
        }
        confirmLabel={isDeleting ? t('common.actions.deleting') : t('common.actions.delete')}
        cancelLabel={t('common.actions.cancel')}
        isPending={isDeleting}
        onCancel={onDeleteCancel}
        onConfirm={() => {
          void onDeleteConfirm()
        }}
      />
    </Stack>
  )
}

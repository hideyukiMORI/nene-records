import { useState } from 'react'
import type { CreateWebhookInput, Webhook } from '@/entities/webhook'
import {
  useCreateWebhook,
  useDeleteWebhook,
  useUpdateWebhook,
  useWebhookList,
} from '@/entities/webhook'
import { useTranslation } from '@/shared/i18n'
import { Button, ConfirmDialog, EmptyState, Stack, Text } from '@/shared/ui'
import { WebhookForm } from './WebhookForm'

export function ManageWebhooksView() {
  const { t } = useTranslation()
  const { data, isLoading, isError } = useWebhookList()
  const createMutation = useCreateWebhook()
  const updateMutation = useUpdateWebhook()
  const deleteMutation = useDeleteWebhook()

  const [showCreateForm, setShowCreateForm] = useState(false)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Webhook | null>(null)
  const [createError, setCreateError] = useState<string | null>(null)
  const [updateError, setUpdateError] = useState<string | null>(null)

  const handleCreate = async (input: CreateWebhookInput) => {
    setCreateError(null)
    try {
      await createMutation.mutateAsync(input)
      setShowCreateForm(false)
    } catch {
      setCreateError(t('admin.webhooks.createError'))
    }
  }

  const handleUpdate = async (id: number, input: CreateWebhookInput) => {
    setUpdateError(null)
    try {
      await updateMutation.mutateAsync({ id, input })
      setEditingId(null)
    } catch {
      setUpdateError(t('admin.webhooks.updateError'))
    }
  }

  const handleDelete = async () => {
    if (deleteTarget === null) return
    try {
      await deleteMutation.mutateAsync(deleteTarget.id)
    } finally {
      setDeleteTarget(null)
    }
  }

  if (isLoading) {
    return <Text muted>{t('admin.webhooks.loading')}</Text>
  }

  if (isError || data === undefined) {
    return <Text muted>{t('common.error.serverError')}</Text>
  }

  return (
    <Stack gap="lg">
      <div className="flex items-center justify-between gap-4">
        <Text as="h1" variant="heading-md">
          {t('admin.webhooks.title')}
        </Text>
        {!showCreateForm && (
          <Button
            type="button"
            size="sm"
            onClick={() => {
              setShowCreateForm(true)
            }}
          >
            {t('admin.webhooks.addButton')}
          </Button>
        )}
      </div>

      <Text muted>{t('admin.webhooks.description')}</Text>

      {showCreateForm && (
        <WebhookForm
          isSubmitting={createMutation.isPending}
          serverErrorTitle={createError}
          submitLabel={t('common.actions.create')}
          onSubmit={handleCreate}
          onCancel={() => {
            setShowCreateForm(false)
            setCreateError(null)
          }}
        />
      )}

      {data.items.length === 0 && !showCreateForm ? (
        <EmptyState
          title={t('admin.webhooks.empty.title')}
          description={t('admin.webhooks.empty.description')}
        />
      ) : (
        <Stack gap="sm">
          {data.items.map((webhook) =>
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
                isSubmitting={updateMutation.isPending}
                serverErrorTitle={updateError}
                submitLabel={t('common.actions.save')}
                onSubmit={async (input) => handleUpdate(webhook.id, input)}
                onCancel={() => {
                  setEditingId(null)
                  setUpdateError(null)
                }}
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
                      className="truncate font-sans text-body text-blue-600 hover:underline dark:text-blue-400"
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
                      setEditingId(webhook.id)
                    }}
                  >
                    {t('common.actions.edit')}
                  </Button>
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      setDeleteTarget(webhook)
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
        confirmLabel={
          deleteMutation.isPending ? t('common.actions.deleting') : t('common.actions.delete')
        }
        cancelLabel={t('common.actions.cancel')}
        isPending={deleteMutation.isPending}
        onCancel={() => {
          setDeleteTarget(null)
        }}
        onConfirm={() => {
          void handleDelete()
        }}
      />
    </Stack>
  )
}

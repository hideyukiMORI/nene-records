import type {
  CreateNotificationChannelInput,
  NotificationChannel,
  NotificationChannelList,
  UpdateNotificationChannelInput,
} from '@/entities/notification-channel'
import { useTranslation } from '@/shared/i18n'
import { Button, ConfirmDialog, EmptyState, Stack, Text } from '@/shared/ui'
import { NotificationChannelForm } from './NotificationChannelForm'

interface ManageNotificationChannelsViewProps {
  channels: NotificationChannelList | undefined
  isLoading: boolean
  isError: boolean
  showCreateForm: boolean
  editingId: number | null
  deleteTarget: NotificationChannel | null
  testingId: number | null
  isCreating: boolean
  isUpdating: boolean
  isDeleting: boolean
  createError: string | null
  updateError: string | null
  testSuccess: number | null
  testError: number | null
  onCreate: (input: CreateNotificationChannelInput) => Promise<void>
  onUpdate: (id: number, input: UpdateNotificationChannelInput) => Promise<void>
  onToggleEnabled: (channel: NotificationChannel) => Promise<void>
  onTest: (id: number) => Promise<void>
  onDeleteConfirm: () => Promise<void>
  onShowCreateForm: () => void
  onHideCreateForm: () => void
  onStartEdit: (id: number) => void
  onCancelEdit: () => void
  onDeleteRequest: (channel: NotificationChannel) => void
  onDeleteCancel: () => void
}

// テーマ安全なトークン化チップに統一（旧 dark: 変種は data-admin-theme 機構では効かなかった）
const CHANNEL_TYPE_CHIP = 'bg-surface-overlay text-text-muted'
const CHANNEL_TYPE_COLORS: Record<string, string> = {
  email: CHANNEL_TYPE_CHIP,
  slack: CHANNEL_TYPE_CHIP,
  discord: CHANNEL_TYPE_CHIP,
  chatwork: CHANNEL_TYPE_CHIP,
  webhook: CHANNEL_TYPE_CHIP,
}

export function ManageNotificationChannelsView({
  channels,
  isLoading,
  isError,
  showCreateForm,
  editingId,
  deleteTarget,
  testingId,
  isCreating,
  isUpdating,
  isDeleting,
  createError,
  updateError,
  testSuccess,
  testError,
  onCreate,
  onUpdate,
  onToggleEnabled,
  onTest,
  onDeleteConfirm,
  onShowCreateForm,
  onHideCreateForm,
  onStartEdit,
  onCancelEdit,
  onDeleteRequest,
  onDeleteCancel,
}: ManageNotificationChannelsViewProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.notifications.loading')}</Text>
  }

  if (isError || channels === undefined) {
    return <Text muted>{t('admin.notifications.error')}</Text>
  }

  return (
    <Stack gap="lg">
      <div className="flex items-center justify-between gap-4">
        <Stack gap="xs">
          <Text as="h1" variant="heading-md">
            {t('admin.notifications.title')}
          </Text>
          <Text muted>{t('admin.notifications.description')}</Text>
        </Stack>
        {!showCreateForm && (
          <Button type="button" size="sm" onClick={onShowCreateForm}>
            {t('admin.notifications.addButton')}
          </Button>
        )}
      </div>

      {showCreateForm && (
        <NotificationChannelForm
          isSubmitting={isCreating}
          serverErrorTitle={createError}
          submitLabel={t('common.actions.create')}
          onSubmit={onCreate}
          onCancel={onHideCreateForm}
        />
      )}

      {channels.items.length === 0 && !showCreateForm ? (
        <EmptyState
          title={t('admin.notifications.empty.title')}
          description={t('admin.notifications.empty.description')}
        />
      ) : (
        <Stack gap="sm">
          {channels.items.map((channel) =>
            editingId === channel.id ? (
              <NotificationChannelForm
                key={channel.id}
                defaultValues={channel}
                isSubmitting={isUpdating}
                serverErrorTitle={updateError}
                submitLabel={t('common.actions.save')}
                onSubmit={async (input) => onUpdate(channel.id, input)}
                onCancel={onCancelEdit}
              />
            ) : (
              <div
                key={channel.id}
                className="flex flex-col gap-2 rounded-md border border-border bg-surface-raised p-inline-md shadow-sm sm:flex-row sm:items-center sm:justify-between"
              >
                <div className="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                  <span
                    className={`inline-block rounded px-1.5 py-0.5 font-sans text-caption font-medium ${CHANNEL_TYPE_COLORS[channel.channelType] ?? ''}`}
                  >
                    {t(`admin.notifications.channelType.${channel.channelType}`)}
                  </span>
                  <span className="font-sans text-sm font-medium text-text">{channel.label}</span>
                  <span
                    className={`inline-block rounded px-1.5 py-0.5 font-sans text-caption ${
                      channel.isEnabled
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-surface text-text-muted'
                    }`}
                  >
                    {channel.isEnabled
                      ? t('admin.notifications.status.enabled')
                      : t('admin.notifications.status.disabled')}
                  </span>
                  {testSuccess === channel.id && (
                    <span className="font-sans text-caption text-green-600 dark:text-green-400">
                      {t('admin.notifications.testSent')}
                    </span>
                  )}
                  {testError === channel.id && (
                    <span className="font-sans text-caption text-error">
                      {t('admin.notifications.testFailed')}
                    </span>
                  )}
                </div>
                <div className="flex shrink-0 flex-wrap gap-2">
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    disabled={isUpdating}
                    onClick={() => {
                      void onToggleEnabled(channel)
                    }}
                  >
                    {channel.isEnabled
                      ? t('admin.notifications.actions.disable')
                      : t('admin.notifications.actions.enable')}
                  </Button>
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    disabled={testingId === channel.id}
                    onClick={() => {
                      void onTest(channel.id)
                    }}
                  >
                    {testingId === channel.id
                      ? t('admin.notifications.actions.testing')
                      : t('admin.notifications.actions.test')}
                  </Button>
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      onStartEdit(channel.id)
                    }}
                  >
                    {t('common.actions.edit')}
                  </Button>
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => {
                      onDeleteRequest(channel)
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
        title={t('admin.notifications.delete.title')}
        description={
          deleteTarget !== null
            ? t('admin.notifications.delete.description', { label: deleteTarget.label })
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

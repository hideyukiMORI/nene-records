import type { User, UserRole } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, EmptyState, Stack, Text } from '@/shared/ui'
import { IconKey, IconUsers } from '@/shared/ui/icons/Icons'

export interface UserListPanelProps {
  users: User[]
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  currentUserEmail: string | null
  onRetry: () => void
  onChangeRole: (user: User, role: UserRole) => Promise<void>
  onResetPassword: (user: User) => void
  onDelete: (user: User) => void
}

const ROLE_OPTIONS: UserRole[] = ['admin', 'editor']

export function UserListPanel({
  users,
  isLoading,
  isError,
  errorTitle,
  currentUserEmail,
  onRetry,
  onChangeRole,
  onResetPassword,
  onDelete,
}: UserListPanelProps) {
  const { t } = useTranslation()

  if (isLoading) {
    return <Text muted>{t('admin.users.list.loading')}</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text muted>{errorTitle ?? t('admin.users.list.error')}</Text>
        <Button variant="secondary" size="sm" onClick={onRetry}>
          {t('admin.users.list.retry')}
        </Button>
      </Stack>
    )
  }

  if (users.length === 0) {
    return (
      <EmptyState
        title={t('admin.users.list.empty.title')}
        description={t('admin.users.list.empty.description')}
      />
    )
  }

  return (
    <Stack gap="sm">
      {users.map((user) => (
        <div
          key={user.id}
          className="flex flex-col gap-2 rounded-md border border-border bg-surface-raised p-3 sm:flex-row sm:items-center sm:gap-4"
        >
          {/* Avatar placeholder */}
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-overlay text-text-muted">
            <IconUsers size={16} />
          </div>

          {/* User info */}
          <div className="min-w-0 flex-1">
            <div className="truncate text-sm font-medium text-text-primary">{user.email}</div>
            <div className="flex items-center gap-2 text-caption text-text-muted">
              <span
                className={[
                  'rounded px-1.5 py-0.5 text-caption font-medium',
                  user.status === 'invited'
                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                    : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                ].join(' ')}
              >
                {t(
                  user.status === 'invited'
                    ? 'admin.users.status.invited'
                    : 'admin.users.status.active',
                )}
              </span>
            </div>
          </div>

          {/* Role selector */}
          <select
            aria-label={t('admin.users.list.roleLabel')}
            value={user.role}
            disabled={user.email === currentUserEmail}
            onChange={(e) => {
              void onChangeRole(user, e.target.value as UserRole)
            }}
            className="rounded-md border border-border bg-surface px-2 py-1.5 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-accent disabled:cursor-not-allowed disabled:opacity-50"
          >
            {ROLE_OPTIONS.map((role) => (
              <option key={role} value={role}>
                {t(role === 'admin' ? 'admin.users.role.admin' : 'admin.users.role.editor')}
              </option>
            ))}
          </select>

          {/* Action buttons */}
          <div className="flex shrink-0 gap-1">
            <Button
              variant="ghost"
              size="sm"
              title={t('admin.users.resetPassword.submit')}
              onClick={() => {
                onResetPassword(user)
              }}
            >
              <IconKey size={14} />
            </Button>
            <Button
              variant="danger"
              size="sm"
              disabled={user.email === currentUserEmail}
              title={t('admin.users.delete')}
              onClick={() => {
                onDelete(user)
              }}
            >
              {t('admin.users.delete')}
            </Button>
          </div>
        </div>
      ))}
    </Stack>
  )
}

import { Link } from 'react-router-dom'
import type { User, UserRole } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import {
  Button,
  Card,
  EmptyState,
  ErrorState,
  LoadingState,
  Select,
  Stack,
  StatusBadge,
} from '@/shared/ui'
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
    return <LoadingState>{t('admin.users.list.loading')}</LoadingState>
  }

  if (isError) {
    return (
      <ErrorState
        message={errorTitle ?? t('admin.users.list.error')}
        onRetry={onRetry}
        retryLabel={t('admin.users.list.retry')}
      />
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
        <Card
          key={user.id}
          padding="none"
          className="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:gap-4"
        >
          {/* Avatar placeholder */}
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-overlay text-text-muted">
            <IconUsers size={16} />
          </div>

          {/* User info */}
          <div className="min-w-0 flex-1">
            <div className="truncate text-sm font-medium text-text-primary">{user.email}</div>
            <div className="mt-0.5 flex items-center gap-2">
              <StatusBadge status={user.status === 'invited' ? 'draft' : 'published'}>
                {t(
                  user.status === 'invited'
                    ? 'admin.users.status.invited'
                    : 'admin.users.status.active',
                )}
              </StatusBadge>
            </div>
          </div>

          {/* Role selector */}
          <Select
            size="sm"
            aria-label={t('admin.users.list.roleLabel')}
            value={user.role}
            disabled={user.email === currentUserEmail}
            onChange={(e) => {
              void onChangeRole(user, e.target.value as UserRole)
            }}
          >
            {ROLE_OPTIONS.map((role) => (
              <option key={role} value={role}>
                {t(role === 'admin' ? 'admin.users.role.admin' : 'admin.users.role.editor')}
              </option>
            ))}
          </Select>

          {/* Action buttons */}
          <div className="flex shrink-0 gap-1">
            <Link to={`/admin/users/${String(user.id)}`}>
              <Button variant="secondary" size="sm" title={t('admin.users.edit.button')}>
                {t('admin.users.edit.button')}
              </Button>
            </Link>
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
        </Card>
      ))}
    </Stack>
  )
}

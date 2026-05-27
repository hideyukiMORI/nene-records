import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { currentUserIsAdmin } from '@/entities/auth'
import { useChangeEmail, useUpdateUserProfile, useUserById } from '@/entities/user'
import { useTranslation } from '@/shared/i18n'
import { Button, Input, Stack, Text } from '@/shared/ui'

export function UserEditPage() {
  const { t } = useTranslation()
  const { id } = useParams<{ id: string }>()
  const userId = Number(id ?? '0')
  const isAdmin = currentUserIsAdmin()

  const { data: user, isLoading, isError } = useUserById(userId)
  const changeEmailMutation = useChangeEmail()
  const updateProfileMutation = useUpdateUserProfile()

  // Email form state
  const [emailValue, setEmailValue] = useState('')
  const [emailError, setEmailError] = useState<string | null>(null)
  const [emailSuccess, setEmailSuccess] = useState(false)

  // Profile form state
  const [displayName, setDisplayName] = useState('')
  const [fullName, setFullName] = useState('')
  const [jobTitle, setJobTitle] = useState('')
  const [profileSuccess, setProfileSuccess] = useState(false)

  // Initialise form state once user data loads
  const [initialised, setInitialised] = useState(false)
  if (user !== undefined && !initialised) {
    setEmailValue(user.email)
    setDisplayName(user.displayName ?? '')
    setFullName(user.fullName ?? '')
    setJobTitle(user.jobTitle ?? '')
    setInitialised(true)
  }

  if (!isAdmin) {
    return (
      <Stack gap="md">
        <Text as="h1" variant="heading-md">
          {t('admin.users.edit.pageTitle')}
        </Text>
        <Text muted>{t('admin.users.edit.forbidden')}</Text>
      </Stack>
    )
  }

  if (isLoading) {
    return <Text muted>{t('admin.users.edit.loading')}</Text>
  }

  if (isError || user === undefined) {
    return (
      <Stack gap="md">
        <Text muted>{t('admin.users.edit.notFound')}</Text>
        <Link to="/admin/users">
          <Button variant="secondary" size="sm">
            {t('admin.users.edit.backToList')}
          </Button>
        </Link>
      </Stack>
    )
  }

  const handleEmailSave = async () => {
    setEmailError(null)
    setEmailSuccess(false)
    const trimmed = emailValue.trim()
    if (trimmed === '') {
      setEmailError(t('admin.users.validation.emailRequired'))
      return
    }
    // Basic format validation
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
      setEmailError(t('admin.users.validation.emailInvalid'))
      return
    }
    try {
      await changeEmailMutation.mutateAsync({ id: userId, input: { email: trimmed } })
      setEmailSuccess(true)
    } catch (err) {
      const e = err as { title?: string }
      setEmailError(e.title ?? t('admin.users.edit.email.error'))
    }
  }

  const handleProfileSave = async () => {
    setProfileSuccess(false)
    try {
      await updateProfileMutation.mutateAsync({
        id: userId,
        input: {
          displayName: displayName.trim() !== '' ? displayName.trim() : null,
          fullName: fullName.trim() !== '' ? fullName.trim() : null,
          jobTitle: jobTitle.trim() !== '' ? jobTitle.trim() : null,
        },
      })
      setProfileSuccess(true)
    } catch {
      // error surfaced via mutation.error below
    }
  }

  return (
    <Stack gap="lg">
      {/* Page header */}
      <Stack gap="xs">
        <div className="flex items-center gap-3">
          <Link to="/admin/users" className="text-text-muted hover:text-text-primary">
            ← {t('admin.users.edit.backToList')}
          </Link>
        </div>
        <Text as="h1" variant="heading-md">
          {t('admin.users.edit.pageTitle')}
        </Text>
        <Text muted>{user.email}</Text>
      </Stack>

      {/* Email section */}
      <section className="rounded-lg border border-border bg-surface-raised p-6">
        <Stack gap="md">
          <Text as="h2" variant="heading-sm">
            {t('admin.users.edit.email.title')}
          </Text>
          <Input
            id="edit-email"
            label={t('admin.users.edit.email.label')}
            type="email"
            value={emailValue}
            onChange={(e) => {
              setEmailValue(e.target.value)
              setEmailSuccess(false)
            }}
            error={emailError ?? undefined}
            autoComplete="off"
          />
          {emailSuccess ? <Text muted>{t('admin.users.edit.email.saved')}</Text> : null}
          {changeEmailMutation.error !== null ? (
            <Text muted>{changeEmailMutation.error.title}</Text>
          ) : null}
          <div>
            <Button
              variant="primary"
              size="sm"
              disabled={changeEmailMutation.isPending}
              onClick={() => {
                void handleEmailSave()
              }}
            >
              {changeEmailMutation.isPending
                ? t('admin.users.edit.saving')
                : t('admin.users.edit.email.save')}
            </Button>
          </div>
        </Stack>
      </section>

      {/* Profile section */}
      <section className="rounded-lg border border-border bg-surface-raised p-6">
        <Stack gap="md">
          <Text as="h2" variant="heading-sm">
            {t('admin.users.edit.profile.title')}
          </Text>
          <Input
            id="edit-display-name"
            label={t('admin.users.edit.profile.displayName')}
            value={displayName}
            onChange={(e) => {
              setDisplayName(e.target.value)
              setProfileSuccess(false)
            }}
          />
          <Input
            id="edit-full-name"
            label={t('admin.users.edit.profile.fullName')}
            value={fullName}
            onChange={(e) => {
              setFullName(e.target.value)
              setProfileSuccess(false)
            }}
          />
          <Input
            id="edit-job-title"
            label={t('admin.users.edit.profile.jobTitle')}
            value={jobTitle}
            onChange={(e) => {
              setJobTitle(e.target.value)
              setProfileSuccess(false)
            }}
          />
          {profileSuccess ? <Text muted>{t('admin.users.edit.profile.saved')}</Text> : null}
          {updateProfileMutation.error !== null ? (
            <Text muted>{updateProfileMutation.error.title}</Text>
          ) : null}
          <div>
            <Button
              variant="primary"
              size="sm"
              disabled={updateProfileMutation.isPending}
              onClick={() => {
                void handleProfileSave()
              }}
            >
              {updateProfileMutation.isPending
                ? t('admin.users.edit.saving')
                : t('admin.users.edit.profile.save')}
            </Button>
          </div>
        </Stack>
      </section>
    </Stack>
  )
}

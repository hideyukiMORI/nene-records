import { useState } from 'react'
import { useSystemConfig, useUpdateSystemConfig } from '@/entities/system-config'
import type { TenantResolutionMode } from '@/entities/system-config'
import { type MessageKey, useTranslation } from '@/shared/i18n'
import { Button, Card, Input, PageHeader, Stack, Text } from '@/shared/ui'
import { useToast } from '@/shared/ui'

const MODES: { value: TenantResolutionMode; labelKey: MessageKey; descKey: MessageKey }[] = [
  {
    value: 'single',
    labelKey: 'admin.superSettings.mode.single.label',
    descKey: 'admin.superSettings.mode.single.desc',
  },
  {
    value: 'subdomain',
    labelKey: 'admin.superSettings.mode.subdomain.label',
    descKey: 'admin.superSettings.mode.subdomain.desc',
  },
  {
    value: 'path',
    labelKey: 'admin.superSettings.mode.path.label',
    descKey: 'admin.superSettings.mode.path.desc',
  },
]

export function SettingsPage() {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const { data, isLoading } = useSystemConfig()
  const update = useUpdateSystemConfig()

  // undefined = unedited → fall back to loaded data (no useEffect needed)
  const [mode, setMode] = useState<TenantResolutionMode | undefined>(undefined)
  const [orgSlug, setOrgSlug] = useState<string | undefined>(undefined)
  const [baseDomain, setBaseDomain] = useState<string | undefined>(undefined)

  const currentMode = mode ?? data?.tenantResolutionMode ?? 'single'
  const currentOrgSlug = orgSlug ?? data?.tenantOrgSlug ?? ''
  const currentBaseDomain = baseDomain ?? data?.tenantBaseDomain ?? 'localhost'

  function handleSubmit(e: React.SyntheticEvent) {
    e.preventDefault()
    update.mutate(
      {
        tenantResolutionMode: currentMode,
        tenantOrgSlug: currentOrgSlug.trim(),
        tenantBaseDomain: currentBaseDomain.trim(),
      },
      {
        onSuccess: () => {
          showToast(t('admin.superSettings.toast.saved'), 'success')
        },
        onError: () => {
          showToast(t('admin.superSettings.toast.error'), 'error')
        },
      },
    )
  }

  if (isLoading) {
    return <Text muted>{t('admin.superSettings.loading')}</Text>
  }

  return (
    <Stack gap="lg">
      <PageHeader
        title={t('admin.superSettings.pageTitle')}
        description={t('admin.superSettings.pageDesc')}
      />

      <form onSubmit={handleSubmit}>
        <Card padding="none" className="p-6">
          <Text as="h2" variant="heading-sm">
            {t('admin.superSettings.resolutionTitle')}
          </Text>
          <Text muted className="mt-1 text-sm">
            {t('admin.superSettings.resolutionDesc')}
          </Text>

          <Stack gap="sm" className="mt-5">
            {MODES.map((m) => (
              <div
                key={m.value}
                className={[
                  'flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors',
                  currentMode === m.value
                    ? 'border-accent bg-accent/5'
                    : 'border-border bg-surface hover:bg-surface-raised/50',
                ].join(' ')}
              >
                <input
                  id={`mode-${m.value}`}
                  type="radio"
                  name="resolution_mode"
                  value={m.value}
                  checked={currentMode === m.value}
                  onChange={() => {
                    setMode(m.value)
                  }}
                  className="mt-0.5 accent-accent"
                />
                <div>
                  <label
                    htmlFor={`mode-${m.value}`}
                    className="cursor-pointer text-sm font-medium text-text-primary"
                  >
                    {t(m.labelKey)}
                  </label>
                  <p className="mt-0.5 text-xs text-text-muted">{t(m.descKey)}</p>
                </div>
              </div>
            ))}
          </Stack>

          {currentMode === 'single' && (
            <div className="mt-5">
              <label
                htmlFor="tenant-org-slug"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                {t('admin.superSettings.orgSlugLabel')}
              </label>
              <Input
                id="tenant-org-slug"
                value={currentOrgSlug}
                onChange={(e) => {
                  setOrgSlug(e.target.value)
                }}
                placeholder="my-org"
                className="max-w-xs"
              />
              <p className="mt-1 text-xs text-text-muted">{t('admin.superSettings.orgSlugHelp')}</p>
            </div>
          )}

          {currentMode === 'subdomain' && (
            <div className="mt-5">
              <label
                htmlFor="tenant-base-domain"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                {t('admin.superSettings.baseDomainLabel')}
              </label>
              <Input
                id="tenant-base-domain"
                value={currentBaseDomain}
                onChange={(e) => {
                  setBaseDomain(e.target.value)
                }}
                placeholder="example.com"
                className="max-w-xs"
              />
              <p className="mt-1 text-xs text-text-muted">
                {t('admin.superSettings.baseDomainHelp')}
              </p>
            </div>
          )}

          {currentMode === 'path' && (
            <div className="mt-5 rounded-md bg-surface p-3 text-xs text-text-muted">
              {t('admin.superSettings.pathHelp')}
            </div>
          )}

          <div className="mt-6">
            <Button type="submit" variant="primary" disabled={update.isPending}>
              {update.isPending ? t('admin.superSettings.saving') : t('admin.superSettings.save')}
            </Button>
          </div>
        </Card>
      </form>

      {data !== undefined && (
        <Card padding="none" className="p-6">
          <Text as="h2" variant="heading-sm">
            {t('admin.superSettings.currentTitle')}
          </Text>
          <dl className="mt-3 space-y-2 text-sm">
            <div className="flex gap-3">
              <dt className="w-44 shrink-0 text-text-muted">
                {t('admin.superSettings.currentResolution')}
              </dt>
              <dd className="font-mono text-text-primary">{data.tenantResolutionMode}</dd>
            </div>
            {data.tenantOrgSlug !== '' && (
              <div className="flex gap-3">
                <dt className="w-44 shrink-0 text-text-muted">
                  {t('admin.superSettings.orgSlugLabel')}
                </dt>
                <dd className="font-mono text-text-primary">{data.tenantOrgSlug}</dd>
              </div>
            )}
            {data.tenantResolutionMode === 'subdomain' && (
              <div className="flex gap-3">
                <dt className="w-44 shrink-0 text-text-muted">
                  {t('admin.superSettings.baseDomainLabel')}
                </dt>
                <dd className="font-mono text-text-primary">{data.tenantBaseDomain}</dd>
              </div>
            )}
          </dl>
        </Card>
      )}
    </Stack>
  )
}

import { useRef } from 'react'
import { Link } from 'react-router-dom'
import { PLANS } from '@/entities/organization'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, ConfirmDialog, Input, PageHeader, Select, Stack, Text } from '@/shared/ui'
import { IconChevronLeft, IconDownload, IconUpload } from '@/shared/ui/icons/Icons'
import type { ManageOrganizationDetailPageState } from '../hooks/useManageOrganizationDetailPage'

export function ManageOrganizationDetailView({
  org,
  isLoading,
  isError,
  isUpdating,
  isDeleting,
  isExporting,
  isImporting,
  showDeleteConfirm,
  currentName,
  currentSlug,
  currentPlan,
  currentIsActive,
  currentCustomDomain,
  onNameChange,
  onSlugChange,
  onPlanChange,
  onIsActiveChange,
  onCustomDomainChange,
  onUpdate,
  onExport,
  onImportFile,
  onDeleteRequest,
  onDeleteConfirm,
  onDeleteCancel,
}: ManageOrganizationDetailPageState) {
  const { t } = useTranslation()
  const importFileRef = useRef<HTMLInputElement>(null)

  if (isLoading) {
    return <Text muted>{t('admin.organizations.detail.loading')}</Text>
  }

  if (isError || org === undefined) {
    return (
      <Stack gap="md">
        <Text muted>{t('admin.organizations.detail.notFound')}</Text>
        <Link
          to="/superadmin/organizations"
          className="text-sm text-accent hover:text-accent-hover"
        >
          ← {t('admin.organizations.detail.backToList')}
        </Link>
      </Stack>
    )
  }

  return (
    <Stack gap="lg">
      <div>
        <Link
          to="/superadmin/organizations"
          className="mb-2 flex items-center gap-1 text-sm text-text-secondary hover:text-text-primary"
        >
          <IconChevronLeft size={14} />
          {t('admin.organizations.pageTitle')}
        </Link>
        <PageHeader
          title={org.name}
          description={t('admin.organizations.detail.meta', {
            id: org.id,
            date: org.createdAt ?? '—',
          })}
        />
      </div>

      {/* Edit form */}
      <form onSubmit={onUpdate}>
        <Card padding="none" className="p-6">
          <Text as="h2" variant="heading-sm">
            {t('admin.organizations.detail.settingsTitle')}
          </Text>
          <Stack gap="md" className="mt-4">
            <div>
              <label
                htmlFor="edit-org-name"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                {t('common.field.name')} *
              </label>
              <Input
                id="edit-org-name"
                value={currentName}
                onChange={(e) => {
                  onNameChange(e.target.value)
                }}
                placeholder={t('admin.organizations.form.namePlaceholder')}
                required
              />
            </div>

            <div>
              <label
                htmlFor="edit-org-slug"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                {t('common.field.slug')} *
              </label>
              <Input
                id="edit-org-slug"
                value={currentSlug}
                onChange={(e) => {
                  onSlugChange(e.target.value)
                }}
                placeholder={t('admin.organizations.form.slugPlaceholder')}
                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                title={t('admin.organizations.form.slugTitle')}
                required
              />
            </div>

            <div>
              <label
                htmlFor="edit-org-plan"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                {t('admin.organizations.plan')} *
              </label>
              <Select
                id="edit-org-plan"
                value={currentPlan}
                onChange={(e) => {
                  onPlanChange(e.target.value)
                }}
                className="w-full"
              >
                {PLANS.map((p) => (
                  <option key={p} value={p}>
                    {p.charAt(0).toUpperCase() + p.slice(1)}
                  </option>
                ))}
              </Select>
            </div>

            <div>
              <label
                htmlFor="edit-org-domain"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                {t('admin.account.customDomainLabel')}
              </label>
              <Input
                id="edit-org-domain"
                value={currentCustomDomain}
                onChange={(e) => {
                  onCustomDomainChange(e.target.value)
                }}
                placeholder={t('admin.organizations.form.domainPlaceholder')}
              />
            </div>

            <div className="flex items-center gap-2">
              <input
                id="edit-is-active"
                type="checkbox"
                checked={currentIsActive}
                onChange={(e) => {
                  onIsActiveChange(e.target.checked)
                }}
                className="h-4 w-4 rounded border-border accent-accent"
              />
              <label htmlFor="edit-is-active" className="text-sm font-medium text-text-primary">
                {t('admin.organizations.active')}
              </label>
            </div>

            <div className="flex gap-2">
              <Button type="submit" variant="primary" disabled={isUpdating}>
                {isUpdating ? t('common.actions.saving') : t('common.actions.save')}
              </Button>
            </div>
          </Stack>
        </Card>
      </form>

      {/* Export / Import */}
      <Card padding="none" className="p-6">
        <Text as="h2" variant="heading-sm">
          {t('admin.organizations.detail.exportImportTitle')}
        </Text>
        <Text muted className="mt-1">
          {t('admin.organizations.detail.exportImportDesc')}
        </Text>

        <div className="mt-4 flex flex-wrap gap-3">
          <Button
            variant="secondary"
            onClick={() => {
              void onExport()
            }}
            disabled={isExporting}
          >
            <IconDownload size={14} className="mr-1.5" />
            {isExporting
              ? t('admin.organizations.detail.exporting')
              : t('admin.organizations.detail.exportButton')}
          </Button>

          <Button
            variant="secondary"
            onClick={() => {
              importFileRef.current?.click()
            }}
            disabled={isImporting}
          >
            <IconUpload size={14} className="mr-1.5" />
            {isImporting
              ? t('admin.organizations.detail.importing')
              : t('admin.organizations.detail.importButton')}
          </Button>
          <input
            ref={importFileRef}
            type="file"
            accept="application/json,.json"
            className="hidden"
            onChange={(e) => {
              const file = e.target.files?.[0]
              if (file !== undefined) {
                onImportFile(file)
              }
              // Reset file input so the same file can be selected again
              if (importFileRef.current !== null) {
                importFileRef.current.value = ''
              }
            }}
          />
        </div>
      </Card>

      {/* Danger zone */}
      <div className="rounded-lg border border-danger bg-danger-weak p-6">
        <Text as="h2" variant="heading-sm">
          {t('admin.organizations.detail.dangerTitle')}
        </Text>
        <Text muted className="mt-1">
          {t('admin.organizations.detail.dangerDesc')}
        </Text>
        <Button variant="danger" className="mt-4" onClick={onDeleteRequest}>
          {t('admin.organizations.delete.title')}
        </Button>
      </div>

      <ConfirmDialog
        open={showDeleteConfirm}
        title={t('admin.organizations.delete.title')}
        description={t('admin.organizations.detail.deleteConfirmDesc', { name: org.name })}
        confirmLabel={t('admin.organizations.detail.deleteConfirm')}
        isPending={isDeleting}
        onConfirm={onDeleteConfirm}
        onCancel={onDeleteCancel}
      />
    </Stack>
  )
}

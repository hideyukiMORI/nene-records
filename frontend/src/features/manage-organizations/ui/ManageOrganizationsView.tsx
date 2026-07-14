import { useState } from 'react'
import { Link } from 'react-router-dom'
import { PLANS } from '@/entities/organization'
import type { CreateOrganizationInput, Organization } from '@/entities/organization'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, ConfirmDialog, Input, PageHeader, Select, Stack, Text } from '@/shared/ui'
import { IconBuilding, IconX } from '@/shared/ui/icons/Icons'
import type { ManageOrganizationsPageState } from '../hooks/useManageOrganizationsPage'

// ── Internal create form (per-component exception: local form state only) ─────

interface CreateOrganizationFormProps {
  isCreating: boolean
  onClose: () => void
  onSubmit: (input: CreateOrganizationInput) => void
}

function CreateOrganizationForm({ isCreating, onClose, onSubmit }: CreateOrganizationFormProps) {
  const { t } = useTranslation()
  const [name, setName] = useState('')
  const [slug, setSlug] = useState('')
  const [plan, setPlan] = useState<string>('free')
  const [customDomain, setCustomDomain] = useState('')

  function handleSubmit(e: React.SyntheticEvent) {
    e.preventDefault()
    onSubmit({
      name: name.trim(),
      slug: slug.trim(),
      plan,
      customDomain: customDomain.trim() !== '' ? customDomain.trim() : null,
    })
  }

  return (
    <Card padding="none" className="p-6">
      <div className="mb-4 flex items-center justify-between">
        <Text as="h2" variant="heading-sm">
          {t('admin.organizations.new')}
        </Text>
        <button
          type="button"
          onClick={onClose}
          className="text-text-muted hover:text-text-primary"
          aria-label={t('common.dialog.close')}
        >
          <IconX size={18} />
        </button>
      </div>

      <form onSubmit={handleSubmit}>
        <Stack gap="md">
          <div>
            <label htmlFor="org-name" className="mb-1 block text-sm font-medium text-text-primary">
              {t('common.field.name')} *
            </label>
            <Input
              id="org-name"
              value={name}
              onChange={(e) => {
                setName(e.target.value)
              }}
              placeholder={t('admin.organizations.form.namePlaceholder')}
              required
            />
          </div>

          <div>
            <label htmlFor="org-slug" className="mb-1 block text-sm font-medium text-text-primary">
              {t('common.field.slug')} *
            </label>
            <Input
              id="org-slug"
              value={slug}
              onChange={(e) => {
                setSlug(e.target.value)
              }}
              placeholder={t('admin.organizations.form.slugPlaceholder')}
              pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
              title={t('admin.organizations.form.slugTitle')}
              required
            />
            <p className="mt-1 text-xs text-text-muted">{t('admin.organizations.form.slugHelp')}</p>
          </div>

          <div>
            <label htmlFor="org-plan" className="mb-1 block text-sm font-medium text-text-primary">
              {t('admin.organizations.plan')} *
            </label>
            <Select
              id="org-plan"
              value={plan}
              onChange={(e) => {
                setPlan(e.target.value)
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
              htmlFor="org-domain"
              className="mb-1 block text-sm font-medium text-text-primary"
            >
              {t('admin.account.customDomainLabel')}
            </label>
            <Input
              id="org-domain"
              value={customDomain}
              onChange={(e) => {
                setCustomDomain(e.target.value)
              }}
              placeholder={t('admin.organizations.form.domainPlaceholder')}
            />
            <p className="mt-1 text-xs text-text-muted">
              {t('admin.organizations.form.domainHelp')}
            </p>
          </div>

          <div className="flex gap-2">
            <Button type="submit" variant="primary" disabled={isCreating}>
              {isCreating ? t('common.actions.creating') : t('admin.organizations.form.submit')}
            </Button>
            <Button type="button" variant="ghost" onClick={onClose}>
              {t('common.actions.cancel')}
            </Button>
          </div>
        </Stack>
      </form>
    </Card>
  )
}

// ── Main view ─────────────────────────────────────────────────────────────────

export function ManageOrganizationsView({
  organizations,
  total,
  isLoading,
  isError,
  showCreateForm,
  deleteTarget,
  isCreating,
  isDeleting,
  onShowCreateForm,
  onHideCreateForm,
  onCreate,
  onSetDeleteTarget,
  onConfirmDelete,
}: ManageOrganizationsPageState) {
  const { t } = useTranslation()

  return (
    <Stack gap="lg">
      <PageHeader
        title={t('admin.organizations.pageTitle')}
        description={t('admin.organizations.pageDescription')}
        actions={
          !showCreateForm ? (
            <Button variant="primary" onClick={onShowCreateForm}>
              {t('admin.organizations.new')}
            </Button>
          ) : undefined
        }
      />

      {showCreateForm && (
        <CreateOrganizationForm
          isCreating={isCreating}
          onClose={onHideCreateForm}
          onSubmit={onCreate}
        />
      )}

      {isLoading && <Text muted>{t('admin.organizations.loading')}</Text>}
      {isError && <Text muted>{t('admin.organizations.error')}</Text>}

      {!isLoading && !isError && organizations.length === 0 && (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed border-border py-16 text-center">
          <IconBuilding size={32} className="text-text-muted" />
          <Text muted>{t('admin.organizations.empty')}</Text>
          <Button variant="primary" onClick={onShowCreateForm}>
            {t('admin.organizations.createFirst')}
          </Button>
        </div>
      )}

      {organizations.length > 0 && (
        <div className="overflow-hidden rounded-lg border border-border">
          <table className="w-full text-sm">
            <thead className="border-b border-border bg-surface-raised">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-text-muted">
                  {t('admin.organizations.idColumn')}
                </th>
                <th className="px-4 py-3 text-left font-medium text-text-muted">
                  {t('common.field.name')}
                </th>
                <th className="px-4 py-3 text-left font-medium text-text-muted">
                  {t('common.field.slug')}
                </th>
                <th className="px-4 py-3 text-left font-medium text-text-muted">
                  {t('admin.organizations.plan')}
                </th>
                <th className="px-4 py-3 text-left font-medium text-text-muted">
                  {t('admin.organizations.status')}
                </th>
                <th className="px-4 py-3 text-left font-medium text-text-muted">
                  {t('admin.organizations.actions')}
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {organizations.map((org: Organization) => (
                <tr key={org.id} className="hover:bg-surface-raised/50">
                  <td className="px-4 py-3 text-text-muted">{org.id}</td>
                  <td className="px-4 py-3 font-medium text-text-primary">{org.name}</td>
                  <td className="px-4 py-3 font-mono text-text-muted">{org.slug}</td>
                  <td className="px-4 py-3">
                    <span className="inline-flex rounded-full bg-accent/10 px-2 py-0.5 text-xs font-medium text-accent">
                      {org.plan}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {org.isActive ? (
                      <span className="text-xs text-success">
                        {t('admin.organizations.active')}
                      </span>
                    ) : (
                      <span className="text-xs text-text-muted">
                        {t('admin.organizations.inactive')}
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Link
                        to={`/superadmin/organizations/${String(org.id)}`}
                        className="text-xs text-accent hover:text-accent-hover"
                      >
                        {t('common.actions.edit')}
                      </Link>
                      <button
                        type="button"
                        onClick={() => {
                          onSetDeleteTarget(org)
                        }}
                        className="text-xs text-danger hover:text-danger-hover"
                      >
                        {t('common.actions.delete')}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="border-t border-border px-4 py-2 text-xs text-text-muted">
            {t(
              total === 1
                ? 'admin.organizations.countTotal.one'
                : 'admin.organizations.countTotal.other',
              { count: total },
            )}
          </div>
        </div>
      )}

      <ConfirmDialog
        open={deleteTarget !== null}
        title={t('admin.organizations.delete.title')}
        description={
          deleteTarget !== null
            ? t('admin.organizations.delete.description', { name: deleteTarget.name })
            : ''
        }
        confirmLabel={t('common.actions.delete')}
        isPending={isDeleting}
        onConfirm={onConfirmDelete}
        onCancel={() => {
          onSetDeleteTarget(null)
        }}
      />
    </Stack>
  )
}

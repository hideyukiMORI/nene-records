import { useState } from 'react'
import { Link } from 'react-router-dom'
import { PLANS } from '@/entities/organization'
import type { CreateOrganizationInput, Organization } from '@/entities/organization'
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
          New Organization
        </Text>
        <button
          type="button"
          onClick={onClose}
          className="text-text-muted hover:text-text-primary"
          aria-label="Close"
        >
          <IconX size={18} />
        </button>
      </div>

      <form onSubmit={handleSubmit}>
        <Stack gap="md">
          <div>
            <label htmlFor="org-name" className="mb-1 block text-sm font-medium text-text-primary">
              Name *
            </label>
            <Input
              id="org-name"
              value={name}
              onChange={(e) => {
                setName(e.target.value)
              }}
              placeholder="Acme Corp"
              required
            />
          </div>

          <div>
            <label htmlFor="org-slug" className="mb-1 block text-sm font-medium text-text-primary">
              Slug *
            </label>
            <Input
              id="org-slug"
              value={slug}
              onChange={(e) => {
                setSlug(e.target.value)
              }}
              placeholder="acme"
              pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
              title="Lowercase letters, numbers, and hyphens only"
              required
            />
            <p className="mt-1 text-xs text-text-muted">
              Lowercase letters, numbers, and hyphens only.
            </p>
          </div>

          <div>
            <label htmlFor="org-plan" className="mb-1 block text-sm font-medium text-text-primary">
              Plan *
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
              Custom Domain
            </label>
            <Input
              id="org-domain"
              value={customDomain}
              onChange={(e) => {
                setCustomDomain(e.target.value)
              }}
              placeholder="example.com"
            />
            <p className="mt-1 text-xs text-text-muted">Optional. Leave blank to use subdomain.</p>
          </div>

          <div className="flex gap-2">
            <Button type="submit" variant="primary" disabled={isCreating}>
              {isCreating ? 'Creating…' : 'Create Organization'}
            </Button>
            <Button type="button" variant="ghost" onClick={onClose}>
              Cancel
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
  return (
    <Stack gap="lg">
      <PageHeader
        title="Organizations"
        description="Manage all tenant organizations."
        actions={
          !showCreateForm ? (
            <Button variant="primary" onClick={onShowCreateForm}>
              New Organization
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

      {isLoading && <Text muted>Loading organizations…</Text>}
      {isError && <Text muted>Failed to load organizations.</Text>}

      {!isLoading && !isError && organizations.length === 0 && (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed border-border py-16 text-center">
          <IconBuilding size={32} className="text-text-muted" />
          <Text muted>No organizations yet.</Text>
          <Button variant="primary" onClick={onShowCreateForm}>
            Create first organization
          </Button>
        </div>
      )}

      {organizations.length > 0 && (
        <div className="overflow-hidden rounded-lg border border-border">
          <table className="w-full text-sm">
            <thead className="border-b border-border bg-surface-raised">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-text-secondary">ID</th>
                <th className="px-4 py-3 text-left font-medium text-text-secondary">Name</th>
                <th className="px-4 py-3 text-left font-medium text-text-secondary">Slug</th>
                <th className="px-4 py-3 text-left font-medium text-text-secondary">Plan</th>
                <th className="px-4 py-3 text-left font-medium text-text-secondary">Status</th>
                <th className="px-4 py-3 text-left font-medium text-text-secondary">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {organizations.map((org: Organization) => (
                <tr key={org.id} className="hover:bg-surface-raised/50">
                  <td className="px-4 py-3 text-text-muted">{org.id}</td>
                  <td className="px-4 py-3 font-medium text-text-primary">{org.name}</td>
                  <td className="px-4 py-3 font-mono text-text-secondary">{org.slug}</td>
                  <td className="px-4 py-3">
                    <span className="inline-flex rounded-full bg-accent/10 px-2 py-0.5 text-xs font-medium text-accent">
                      {org.plan}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {org.isActive ? (
                      <span className="text-xs text-success">Active</span>
                    ) : (
                      <span className="text-xs text-text-muted">Inactive</span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Link
                        to={`/superadmin/organizations/${String(org.id)}`}
                        className="text-xs text-accent hover:text-accent-hover"
                      >
                        Edit
                      </Link>
                      <button
                        type="button"
                        onClick={() => {
                          onSetDeleteTarget(org)
                        }}
                        className="text-xs text-danger hover:text-danger-hover"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="border-t border-border px-4 py-2 text-xs text-text-muted">
            {total} organization{total !== 1 ? 's' : ''} total
          </div>
        </div>
      )}

      <ConfirmDialog
        open={deleteTarget !== null}
        title="Delete Organization"
        description={
          deleteTarget !== null ? `Delete "${deleteTarget.name}"? This cannot be undone.` : ''
        }
        confirmLabel="Delete"
        isPending={isDeleting}
        onConfirm={onConfirmDelete}
        onCancel={() => {
          onSetDeleteTarget(null)
        }}
      />
    </Stack>
  )
}

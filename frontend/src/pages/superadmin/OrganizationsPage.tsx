import { useState } from 'react'
import { Link } from 'react-router-dom'
import {
  useOrganizationList,
  useCreateOrganization,
  useDeleteOrganization,
  PLANS,
} from '@/entities/organization'
import type { CreateOrganizationInput, Organization } from '@/entities/organization'
import { Button, ConfirmDialog, Input, Stack, Text } from '@/shared/ui'
import { useToast } from '@/shared/ui'
import { IconBuilding, IconX } from '@/shared/ui/icons/Icons'

interface CreateOrganizationFormProps {
  onClose: () => void
  onSuccess: () => void
}

function CreateOrganizationForm({ onClose, onSuccess }: CreateOrganizationFormProps) {
  const { showToast } = useToast()
  const createOrg = useCreateOrganization()

  const [name, setName] = useState('')
  const [slug, setSlug] = useState('')
  const [plan, setPlan] = useState<string>('free')
  const [customDomain, setCustomDomain] = useState('')

  function handleSubmit(e: React.SyntheticEvent) {
    e.preventDefault()

    const input: CreateOrganizationInput = {
      name: name.trim(),
      slug: slug.trim(),
      plan,
      customDomain: customDomain.trim() !== '' ? customDomain.trim() : null,
    }

    createOrg.mutate(input, {
      onSuccess: () => {
        showToast(`Organization "${input.name}" created.`, 'success')
        onSuccess()
      },
      onError: (err) => {
        showToast(err.title, 'error')
      },
    })
  }

  return (
    <div className="rounded-lg border border-border bg-surface-raised p-6">
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
            <select
              id="org-plan"
              value={plan}
              onChange={(e) => {
                setPlan(e.target.value)
              }}
              className="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary focus:border-accent focus:outline-none"
            >
              {PLANS.map((p) => (
                <option key={p} value={p}>
                  {p.charAt(0).toUpperCase() + p.slice(1)}
                </option>
              ))}
            </select>
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
            <Button type="submit" variant="primary" disabled={createOrg.isPending}>
              {createOrg.isPending ? 'Creating…' : 'Create Organization'}
            </Button>
            <Button type="button" variant="ghost" onClick={onClose}>
              Cancel
            </Button>
          </div>
        </Stack>
      </form>
    </div>
  )
}

export function OrganizationsPage() {
  const { showToast } = useToast()
  const { data, isLoading, isError } = useOrganizationList()
  const deleteOrg = useDeleteOrganization()

  const [showCreateForm, setShowCreateForm] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<Organization | null>(null)

  function handleDelete() {
    if (deleteTarget === null) return

    const targetName = deleteTarget.name
    const targetId = deleteTarget.id

    deleteOrg.mutate(targetId, {
      onSuccess: () => {
        showToast(`Organization "${targetName}" deleted.`, 'success')
        setDeleteTarget(null)
      },
      onError: (err) => {
        showToast(err.title, 'error')
        setDeleteTarget(null)
      },
    })
  }

  return (
    <Stack gap="lg">
      <div className="flex items-start justify-between">
        <div>
          <Text as="h1" variant="heading-md">
            Organizations
          </Text>
          <Text muted>Manage all tenant organizations.</Text>
        </div>
        {!showCreateForm && (
          <Button
            variant="primary"
            onClick={() => {
              setShowCreateForm(true)
            }}
          >
            New Organization
          </Button>
        )}
      </div>

      {showCreateForm && (
        <CreateOrganizationForm
          onClose={() => {
            setShowCreateForm(false)
          }}
          onSuccess={() => {
            setShowCreateForm(false)
          }}
        />
      )}

      {isLoading && <Text muted>Loading organizations…</Text>}
      {isError && <Text muted>Failed to load organizations.</Text>}

      {data !== undefined && data.items.length === 0 && (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed border-border py-16 text-center">
          <IconBuilding size={32} className="text-text-muted" />
          <Text muted>No organizations yet.</Text>
          <Button
            variant="primary"
            onClick={() => {
              setShowCreateForm(true)
            }}
          >
            Create first organization
          </Button>
        </div>
      )}

      {data !== undefined && data.items.length > 0 && (
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
              {data.items.map((org) => (
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
                      <span className="text-xs text-green-600 dark:text-green-400">Active</span>
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
                          setDeleteTarget(org)
                        }}
                        className="text-xs text-red-500 hover:text-red-700"
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
            {data.total} organization{data.total !== 1 ? 's' : ''} total
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
        isPending={deleteOrg.isPending}
        onConfirm={handleDelete}
        onCancel={() => {
          setDeleteTarget(null)
        }}
      />
    </Stack>
  )
}

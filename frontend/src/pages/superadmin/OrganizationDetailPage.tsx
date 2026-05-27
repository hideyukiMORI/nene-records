import { useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import {
  useOrganization,
  useUpdateOrganization,
  useDeleteOrganization,
  PLANS,
} from '@/entities/organization'
import type { UpdateOrganizationInput } from '@/entities/organization'
import { Button, ConfirmDialog, Input, Stack, Text } from '@/shared/ui'
import { useToast } from '@/shared/ui'
import { IconChevronLeft } from '@/shared/ui/icons/Icons'

export function OrganizationDetailPage() {
  const { id } = useParams<{ id: string }>()
  const orgId = Number(id ?? '0')
  const navigate = useNavigate()
  const { showToast } = useToast()

  const { data: org, isLoading, isError } = useOrganization(orgId)
  const updateOrg = useUpdateOrganization()
  const deleteOrg = useDeleteOrganization()

  const [name, setName] = useState<string | undefined>(undefined)
  const [slug, setSlug] = useState<string | undefined>(undefined)
  const [plan, setPlan] = useState<string | undefined>(undefined)
  const [isActive, setIsActive] = useState<boolean | undefined>(undefined)
  const [customDomain, setCustomDomain] = useState<string | undefined>(undefined)
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)

  // Controlled values fall back to loaded org data before user edits
  const currentName = name ?? org?.name ?? ''
  const currentSlug = slug ?? org?.slug ?? ''
  const currentPlan = plan ?? org?.plan ?? 'free'
  const currentIsActive = isActive ?? org?.isActive ?? true
  const currentCustomDomain = customDomain ?? org?.customDomain ?? ''

  function handleUpdate(e: React.SyntheticEvent) {
    e.preventDefault()

    const input: UpdateOrganizationInput = {
      name: currentName.trim(),
      slug: currentSlug.trim(),
      plan: currentPlan,
      isActive: currentIsActive,
      customDomain: currentCustomDomain.trim() !== '' ? currentCustomDomain.trim() : null,
    }

    updateOrg.mutate(
      { id: orgId, input },
      {
        onSuccess: () => {
          showToast('Organization updated.', 'success')
        },
        onError: (err) => {
          showToast(err.title, 'error')
        },
      },
    )
  }

  function handleDelete() {
    const orgName = org?.name ?? ''
    deleteOrg.mutate(orgId, {
      onSuccess: () => {
        showToast(`Organization "${orgName}" deleted.`, 'success')
        void navigate('/superadmin/organizations')
      },
      onError: (err) => {
        showToast(err.title, 'error')
        setShowDeleteConfirm(false)
      },
    })
  }

  if (isLoading) {
    return <Text muted>Loading…</Text>
  }

  if (isError || org === undefined) {
    return (
      <Stack gap="md">
        <Text muted>Organization not found.</Text>
        <Link
          to="/superadmin/organizations"
          className="text-sm text-accent hover:text-accent-hover"
        >
          ← Back to Organizations
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
          Organizations
        </Link>
        <Text as="h1" variant="heading-md">
          {org.name}
        </Text>
        <Text muted>
          ID: {org.id} · Created: {org.createdAt ?? '—'}
        </Text>
      </div>

      {/* Edit form */}
      <form onSubmit={handleUpdate}>
        <div className="rounded-lg border border-border bg-surface-raised p-6">
          <Text as="h2" variant="heading-sm">
            Organization Settings
          </Text>
          <Stack gap="md" className="mt-4">
            <div>
              <label
                htmlFor="edit-org-name"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                Name *
              </label>
              <Input
                id="edit-org-name"
                value={currentName}
                onChange={(e) => {
                  setName(e.target.value)
                }}
                placeholder="Acme Corp"
                required
              />
            </div>

            <div>
              <label
                htmlFor="edit-org-slug"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                Slug *
              </label>
              <Input
                id="edit-org-slug"
                value={currentSlug}
                onChange={(e) => {
                  setSlug(e.target.value)
                }}
                placeholder="acme"
                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                title="Lowercase letters, numbers, and hyphens only"
                required
              />
            </div>

            <div>
              <label
                htmlFor="edit-org-plan"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                Plan *
              </label>
              <select
                id="edit-org-plan"
                value={currentPlan}
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
                htmlFor="edit-org-domain"
                className="mb-1 block text-sm font-medium text-text-primary"
              >
                Custom Domain
              </label>
              <Input
                id="edit-org-domain"
                value={currentCustomDomain}
                onChange={(e) => {
                  setCustomDomain(e.target.value)
                }}
                placeholder="example.com"
              />
            </div>

            <div className="flex items-center gap-2">
              <input
                id="edit-is-active"
                type="checkbox"
                checked={currentIsActive}
                onChange={(e) => {
                  setIsActive(e.target.checked)
                }}
                className="h-4 w-4 rounded border-border accent-accent"
              />
              <label htmlFor="edit-is-active" className="text-sm font-medium text-text-primary">
                Active
              </label>
            </div>

            <div className="flex gap-2">
              <Button type="submit" variant="primary" disabled={updateOrg.isPending}>
                {updateOrg.isPending ? 'Saving…' : 'Save Changes'}
              </Button>
            </div>
          </Stack>
        </div>
      </form>

      {/* Danger zone */}
      <div className="rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-900/40 dark:bg-red-950/20">
        <Text as="h2" variant="heading-sm">
          Danger Zone
        </Text>
        <Text muted className="mt-1">
          Deleting an organization is permanent and cannot be undone.
        </Text>
        <Button
          variant="danger"
          className="mt-4"
          onClick={() => {
            setShowDeleteConfirm(true)
          }}
        >
          Delete Organization
        </Button>
      </div>

      <ConfirmDialog
        open={showDeleteConfirm}
        title="Delete Organization"
        description={`Permanently delete "${org.name}"? All data will be removed. This cannot be undone.`}
        confirmLabel="Delete permanently"
        isPending={deleteOrg.isPending}
        onConfirm={handleDelete}
        onCancel={() => {
          setShowDeleteConfirm(false)
        }}
      />
    </Stack>
  )
}

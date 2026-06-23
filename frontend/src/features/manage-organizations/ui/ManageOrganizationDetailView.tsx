import { useRef } from 'react'
import { Link } from 'react-router-dom'
import { PLANS } from '@/entities/organization'
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
  const importFileRef = useRef<HTMLInputElement>(null)

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
        <PageHeader
          title={org.name}
          description={
            <>
              ID: {org.id} · Created: {org.createdAt ?? '—'}
            </>
          }
        />
      </div>

      {/* Edit form */}
      <form onSubmit={onUpdate}>
        <Card padding="none" className="p-6">
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
                  onNameChange(e.target.value)
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
                  onSlugChange(e.target.value)
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
                Custom Domain
              </label>
              <Input
                id="edit-org-domain"
                value={currentCustomDomain}
                onChange={(e) => {
                  onCustomDomainChange(e.target.value)
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
                  onIsActiveChange(e.target.checked)
                }}
                className="h-4 w-4 rounded border-border accent-accent"
              />
              <label htmlFor="edit-is-active" className="text-sm font-medium text-text-primary">
                Active
              </label>
            </div>

            <div className="flex gap-2">
              <Button type="submit" variant="primary" disabled={isUpdating}>
                {isUpdating ? 'Saving…' : 'Save Changes'}
              </Button>
            </div>
          </Stack>
        </Card>
      </form>

      {/* Export / Import */}
      <Card padding="none" className="p-6">
        <Text as="h2" variant="heading-sm">
          Export &amp; Import
        </Text>
        <Text muted className="mt-1">
          Export all data for this organization as a JSON file, or import a previously exported
          file.
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
            {isExporting ? 'Exporting…' : 'Export JSON'}
          </Button>

          <Button
            variant="secondary"
            onClick={() => {
              importFileRef.current?.click()
            }}
            disabled={isImporting}
          >
            <IconUpload size={14} className="mr-1.5" />
            {isImporting ? 'Importing…' : 'Import JSON'}
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
          Danger Zone
        </Text>
        <Text muted className="mt-1">
          Deleting an organization is permanent and cannot be undone.
        </Text>
        <Button variant="danger" className="mt-4" onClick={onDeleteRequest}>
          Delete Organization
        </Button>
      </div>

      <ConfirmDialog
        open={showDeleteConfirm}
        title="Delete Organization"
        description={`Permanently delete "${org.name}"? All data will be removed. This cannot be undone.`}
        confirmLabel="Delete permanently"
        isPending={isDeleting}
        onConfirm={onDeleteConfirm}
        onCancel={onDeleteCancel}
      />
    </Stack>
  )
}

import {
  ManageOrganizationsView,
  useManageOrganizationsPage,
} from '@/features/manage-organizations'

export function OrganizationsPage() {
  const page = useManageOrganizationsPage()
  return <ManageOrganizationsView {...page} />
}

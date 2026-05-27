import { useParams } from 'react-router-dom'
import {
  ManageOrganizationDetailView,
  useManageOrganizationDetailPage,
} from '@/features/manage-organizations'

export function OrganizationDetailPage() {
  const { id } = useParams<{ id: string }>()
  const orgId = Number(id ?? '0')
  const page = useManageOrganizationDetailPage(orgId)
  return <ManageOrganizationDetailView {...page} />
}

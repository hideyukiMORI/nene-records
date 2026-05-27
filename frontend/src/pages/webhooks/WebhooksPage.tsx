import { ManageWebhooksView, useManageWebhooksPage } from '@/features/manage-webhooks'

export function WebhooksPage() {
  const webhooksPage = useManageWebhooksPage()
  return <ManageWebhooksView {...webhooksPage} />
}

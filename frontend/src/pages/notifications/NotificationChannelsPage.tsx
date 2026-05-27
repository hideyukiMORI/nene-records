import {
  ManageNotificationChannelsView,
  useManageNotificationChannelsPage,
} from '@/features/manage-notification-channels'

export function NotificationChannelsPage() {
  const page = useManageNotificationChannelsPage()
  return <ManageNotificationChannelsView {...page} />
}

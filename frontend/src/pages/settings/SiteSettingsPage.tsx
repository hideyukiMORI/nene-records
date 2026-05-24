import { ManageSiteSettingsView } from '@/features/manage-settings'
import { Stack, Text } from '@/shared/ui'

export function SiteSettingsPage() {
  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        Site settings
      </Text>
      <Text muted>
        Configure site name, tagline, default meta description, and footer content for public pages.
      </Text>
      <ManageSiteSettingsView />
    </Stack>
  )
}

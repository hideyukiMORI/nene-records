import { ManageSiteSettingsView } from '@/features/manage-settings'
import { currentUserHasCapability } from '@/entities/auth'
import { Stack, Text } from '@/shared/ui'

export function SiteSettingsPage() {
  const canManageSettings = currentUserHasCapability('manage_settings')
  return (
    <Stack gap="md">
      <Text as="h1" variant="heading-md">
        Site settings
      </Text>
      <Text muted>
        Configure site name, tagline, default meta description, and footer content for public pages.
      </Text>
      <ManageSiteSettingsView canManageSettings={canManageSettings} />
    </Stack>
  )
}

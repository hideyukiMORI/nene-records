import { Button, Stack, Text } from '@/shared/ui'

export interface EntityCreatePanelProps {
  isSubmitting: boolean
  serverErrorTitle: string | null
  onCreate: () => Promise<void>
}

export function EntityCreatePanel({
  isSubmitting,
  serverErrorTitle,
  onCreate,
}: EntityCreatePanelProps) {
  return (
    <Stack gap="sm">
      <Text as="h2" variant="heading-sm">
        Create record
      </Text>
      <Text muted>
        Records are created for this entity type. Field values will be editable in a later phase.
      </Text>
      {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
      <div>
        <Button
          disabled={isSubmitting}
          onClick={() => {
            void onCreate()
          }}
        >
          {isSubmitting ? 'Creating…' : 'Create record'}
        </Button>
      </div>
    </Stack>
  )
}

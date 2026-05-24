import type { FieldDef } from '@/entities/field-def'
import type { Entity } from '@/entities/entity'
import { Button, Stack, Text } from '@/shared/ui'
import { EntityTextFieldsForm } from './EntityTextFieldsForm'

export interface EditEntityTextFieldsViewProps {
  entity: Entity | null
  textFieldDefs: FieldDef[]
  initialValues: Record<string, string>
  isLoading: boolean
  isError: boolean
  errorTitle: string | null
  isSaving: boolean
  saveErrorTitle: string | null
  onRetry: () => void
  onSave: (values: Record<string, string>) => Promise<void>
}

export function EditEntityTextFieldsView({
  entity,
  textFieldDefs,
  initialValues,
  isLoading,
  isError,
  errorTitle,
  isSaving,
  saveErrorTitle,
  onRetry,
  onSave,
}: EditEntityTextFieldsViewProps) {
  if (isLoading) {
    return <Text muted>Loading record…</Text>
  }

  if (isError) {
    return (
      <Stack gap="sm">
        <Text variant="heading-sm">Could not load record</Text>
        <Text muted>{errorTitle ?? 'Unknown error'}</Text>
        <Button variant="secondary" onClick={onRetry}>
          Retry
        </Button>
      </Stack>
    )
  }

  if (entity === null) {
    return <Text muted>Record not found.</Text>
  }

  return (
    <Stack gap="lg">
      <Text as="p" muted>
        Record #{String(entity.id)}
      </Text>
      <EntityTextFieldsForm
        key={JSON.stringify(initialValues)}
        fieldDefs={textFieldDefs}
        defaultValues={initialValues}
        isSubmitting={isSaving}
        serverErrorTitle={saveErrorTitle}
        onSubmit={onSave}
      />
    </Stack>
  )
}

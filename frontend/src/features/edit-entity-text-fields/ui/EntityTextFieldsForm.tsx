import { useState } from 'react'
import type { FieldDef } from '@/entities/field-def'
import { Button, EmptyState, Input, Stack, Text } from '@/shared/ui'

export interface EntityTextFieldsFormProps {
  fieldDefs: FieldDef[]
  defaultValues: Record<string, string>
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: Record<string, string>) => Promise<void>
}

export function EntityTextFieldsForm({
  fieldDefs,
  defaultValues,
  isSubmitting,
  serverErrorTitle,
  onSubmit,
}: EntityTextFieldsFormProps) {
  const [values, setValues] = useState(defaultValues)

  if (fieldDefs.length === 0) {
    return (
      <EmptyState
        title="No editable fields defined"
        description="Add text or integer field definitions for this entity type first."
      />
    )
  }

  return (
    <form
      className="rounded-md border border-border bg-surface-raised p-inline-md shadow-sm"
      onSubmit={(event) => {
        event.preventDefault()
        void onSubmit(values)
      }}
    >
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          Field values
        </Text>
        {fieldDefs.map((fieldDef) => (
          <Input
            key={fieldDef.fieldKey}
            id={`record-field-${fieldDef.fieldKey}`}
            label={`${fieldDef.fieldKey} (${fieldDef.dataType})`}
            type={fieldDef.dataType === 'int' ? 'number' : 'text'}
            autoComplete="off"
            disabled={isSubmitting}
            value={values[fieldDef.fieldKey] ?? ''}
            onChange={(event) => {
              setValues((current) => ({ ...current, [fieldDef.fieldKey]: event.target.value }))
            }}
          />
        ))}
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Saving…' : 'Save values'}
        </Button>
      </Stack>
    </form>
  )
}

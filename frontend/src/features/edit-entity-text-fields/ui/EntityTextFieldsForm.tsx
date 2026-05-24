import { useState } from 'react'
import type { FieldDataType, FieldDef } from '@/entities/field-def'
import { Button, EmptyState, Input, Stack, Text } from '@/shared/ui'

export interface EntityTextFieldsFormProps {
  fieldDefs: FieldDef[]
  defaultValues: Record<string, string>
  isSubmitting: boolean
  serverErrorTitle: string | null
  onSubmit: (values: Record<string, string>) => Promise<void>
}

function getInputType(dataType: FieldDataType): 'text' | 'number' | 'datetime-local' {
  switch (dataType) {
    case 'int':
      return 'number'
    case 'datetime':
      return 'datetime-local'
    default:
      return 'text'
  }
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
        description="Add field definitions for this entity type first."
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
        {fieldDefs.map((fieldDef) => {
          const fieldId = `record-field-${fieldDef.fieldKey}`
          const label = `${fieldDef.fieldKey} (${fieldDef.dataType})`

          if (fieldDef.dataType === 'bool') {
            return (
              <div key={fieldDef.fieldKey} className="flex flex-col gap-stack-xs">
                <label
                  htmlFor={fieldId}
                  className="flex items-center gap-inline-sm font-sans text-body font-medium text-text-primary"
                >
                  <input
                    id={fieldId}
                    type="checkbox"
                    disabled={isSubmitting}
                    checked={values[fieldDef.fieldKey] === 'true'}
                    onChange={(event) => {
                      setValues((current) => ({
                        ...current,
                        [fieldDef.fieldKey]: event.target.checked ? 'true' : 'false',
                      }))
                    }}
                    className="size-4 rounded border border-border"
                  />
                  {label}
                </label>
              </div>
            )
          }

          return (
            <Input
              key={fieldDef.fieldKey}
              id={fieldId}
              label={label}
              type={getInputType(fieldDef.dataType)}
              autoComplete="off"
              disabled={isSubmitting}
              value={values[fieldDef.fieldKey] ?? ''}
              onChange={(event) => {
                setValues((current) => ({ ...current, [fieldDef.fieldKey]: event.target.value }))
              }}
            />
          )
        })}
        {serverErrorTitle !== null ? <Text muted>{serverErrorTitle}</Text> : null}
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Saving…' : 'Save values'}
        </Button>
      </Stack>
    </form>
  )
}

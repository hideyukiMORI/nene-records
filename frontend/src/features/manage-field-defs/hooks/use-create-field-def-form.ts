import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { FIELD_DATA_TYPES } from '@/entities/field-def'

export const createFieldDefFormSchema = z.object({
  fieldKey: z
    .string()
    .trim()
    .min(1, 'Field key is required')
    .regex(/^[a-z][a-z0-9_]*$/, 'Use lowercase letters, numbers, and underscores'),
  dataType: z.enum(FIELD_DATA_TYPES),
  region: z.enum(['main', 'sidebar', 'aside']),
  displayOrder: z.number().int().min(0),
})

export type CreateFieldDefFormValues = z.infer<typeof createFieldDefFormSchema>

export function useCreateFieldDefForm() {
  return useForm<CreateFieldDefFormValues>({
    resolver: zodResolver(createFieldDefFormSchema),
    defaultValues: {
      fieldKey: '',
      dataType: 'text',
      region: 'main',
      displayOrder: 0,
    },
  })
}

export function useEditFieldDefForm(defaultValues: CreateFieldDefFormValues) {
  return useForm<CreateFieldDefFormValues>({
    resolver: zodResolver(createFieldDefFormSchema),
    defaultValues,
  })
}

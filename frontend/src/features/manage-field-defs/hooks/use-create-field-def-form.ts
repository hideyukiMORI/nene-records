import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { FIELD_DATA_TYPES, RELATION_CARDINALITIES } from '@/entities/field-def'

export const createFieldDefFormSchema = z
  .object({
    fieldKey: z
      .string()
      .trim()
      .min(1, 'Field key is required')
      .regex(/^[a-z][a-z0-9_]*$/, 'Use lowercase letters, numbers, and underscores'),
    dataType: z.enum(FIELD_DATA_TYPES),
    region: z.enum(['main', 'sidebar', 'aside']),
    displayOrder: z.number().int().min(0),
    targetEntityTypeId: z.number().int().positive().optional(),
    cardinality: z.enum(RELATION_CARDINALITIES).optional(),
  })
  // A relation field is meaningless without a target content type.
  .refine((values) => values.dataType !== 'relation' || values.targetEntityTypeId !== undefined, {
    message: 'Select a target content type for relation fields',
    path: ['targetEntityTypeId'],
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
      cardinality: 'one',
    },
  })
}

export function useEditFieldDefForm(defaultValues: CreateFieldDefFormValues) {
  return useForm<CreateFieldDefFormValues>({
    resolver: zodResolver(createFieldDefFormSchema),
    defaultValues,
  })
}

import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

export const createEntityTypeFormSchema = z.object({
  name: z.string().trim().min(1, 'Name is required'),
  slug: z
    .string()
    .trim()
    .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, 'Use lowercase letters, numbers, and hyphens'),
  isPinned: z.boolean().default(false),
})

export type CreateEntityTypeFormValues = z.infer<typeof createEntityTypeFormSchema>

export function useCreateEntityTypeForm() {
  return useForm<CreateEntityTypeFormValues>({
    resolver: zodResolver(createEntityTypeFormSchema),
    defaultValues: {
      name: '',
      slug: '',
      isPinned: false,
    },
  })
}

export function useEditEntityTypeForm(defaultValues: CreateEntityTypeFormValues) {
  return useForm<CreateEntityTypeFormValues>({
    resolver: zodResolver(createEntityTypeFormSchema),
    defaultValues,
  })
}

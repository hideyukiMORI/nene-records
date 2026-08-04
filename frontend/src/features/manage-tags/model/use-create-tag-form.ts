import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

export const createTagFormSchema = z.object({
  name: z.string().trim().min(1, 'Name is required'),
  slug: z
    .string()
    .trim()
    .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, 'Use lowercase letters, numbers, and hyphens'),
})

export type CreateTagFormValues = z.infer<typeof createTagFormSchema>

export function useCreateTagForm() {
  return useForm<CreateTagFormValues>({
    resolver: zodResolver(createTagFormSchema),
    defaultValues: {
      name: '',
      slug: '',
    },
  })
}

export function useEditTagForm(defaultValues: CreateTagFormValues) {
  return useForm<CreateTagFormValues>({
    resolver: zodResolver(createTagFormSchema),
    defaultValues,
  })
}

import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

/**
 * Starter field presets provisioned right after a content type is created
 * (#491 WS1): `rich_page` gives a blocks body so authors compose the page with
 * the block editor without an admin adding fields manually.
 */
export const ENTITY_TYPE_STARTERS = ['blank', 'article', 'rich_page', 'custom_page'] as const
export type EntityTypeStarter = (typeof ENTITY_TYPE_STARTERS)[number]

export const createEntityTypeFormSchema = z.object({
  name: z.string().trim().min(1, 'Name is required'),
  slug: z
    .string()
    .trim()
    .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, 'Use lowercase letters, numbers, and hyphens'),
  isPinned: z.boolean().default(false),
  starter: z.enum(['blank', 'article', 'rich_page', 'custom_page']).default('blank'),
})

export type CreateEntityTypeFormValues = z.infer<typeof createEntityTypeFormSchema>
type CreateEntityTypeFormInput = z.input<typeof createEntityTypeFormSchema>

export const editEntityTypeFormSchema = createEntityTypeFormSchema.extend({
  labelJa: z.string().optional(),
  labelFr: z.string().optional(),
  labelZhHans: z.string().optional(),
  labelPtBr: z.string().optional(),
  labelDe: z.string().optional(),
  /** null = use default pattern (/{type}/{id}); empty string treated as null */
  permalinkPattern: z.string().nullable().optional(),
  /** Default public-page layout for records of this type. */
  defaultLayout: z
    .enum(['standard', 'full', 'two-col', 'three-col', 'bare', 'custom'])
    .default('standard'),
})

export type EditEntityTypeFormValues = z.infer<typeof editEntityTypeFormSchema>
type EditEntityTypeFormInput = z.input<typeof editEntityTypeFormSchema>

/** Maps the flat label_* form fields to a locale-keyed labels Record. */
export function formValuesToLabels(values: EditEntityTypeFormValues): Record<string, string> {
  const labels: Record<string, string> = {}
  if (values.labelJa?.trim()) labels['ja'] = values.labelJa.trim()
  if (values.labelFr?.trim()) labels['fr'] = values.labelFr.trim()
  if (values.labelZhHans?.trim()) labels['zh-Hans'] = values.labelZhHans.trim()
  if (values.labelPtBr?.trim()) labels['pt-BR'] = values.labelPtBr.trim()
  if (values.labelDe?.trim()) labels['de'] = values.labelDe.trim()
  return labels
}

/** Locale-field mapping used to render label inputs in the edit form. */
export const EDIT_LABEL_FIELDS = [
  { fieldName: 'labelJa' as const, localeId: 'ja', nativeLabel: '日本語 (ja)' },
  { fieldName: 'labelFr' as const, localeId: 'fr', nativeLabel: 'Français (fr)' },
  { fieldName: 'labelZhHans' as const, localeId: 'zh-Hans', nativeLabel: '中文（简体）(zh-Hans)' },
  { fieldName: 'labelPtBr' as const, localeId: 'pt-BR', nativeLabel: 'Português (Brasil) (pt-BR)' },
  { fieldName: 'labelDe' as const, localeId: 'de', nativeLabel: 'Deutsch (de)' },
] as const

export function useCreateEntityTypeForm() {
  return useForm<CreateEntityTypeFormInput, unknown, CreateEntityTypeFormValues>({
    resolver: zodResolver(createEntityTypeFormSchema),
    defaultValues: {
      name: '',
      slug: '',
      isPinned: false,
      starter: 'blank',
    },
  })
}

export function useEditEntityTypeForm(defaultValues: EditEntityTypeFormInput) {
  return useForm<EditEntityTypeFormInput, unknown, EditEntityTypeFormValues>({
    resolver: zodResolver(editEntityTypeFormSchema),
    defaultValues,
  })
}

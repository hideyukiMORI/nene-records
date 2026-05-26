import { useState } from 'react'
import type { Entity } from '@/entities/entity'
import { useUpdateEntity } from '@/entities/entity'
import { useTranslation } from '@/shared/i18n'
import { Button, Stack, Text, useToast } from '@/shared/ui'

interface EntitySeoPanelProps {
  entity: Entity
}

export function EntitySeoPanel({ entity }: EntitySeoPanelProps) {
  const { t } = useTranslation()
  const { showToast } = useToast()
  const updateMutation = useUpdateEntity()

  const [metaTitle, setMetaTitle] = useState(entity.metaTitle ?? '')
  const [metaDescription, setMetaDescription] = useState(entity.metaDescription ?? '')

  const save = async () => {
    try {
      await updateMutation.mutateAsync({
        id: Number(entity.id),
        entityTypeId: entity.entityTypeId,
        slug: entity.slug,
        status: entity.status,
        metaTitle: metaTitle !== '' ? metaTitle : null,
        metaDescription: metaDescription !== '' ? metaDescription : null,
      })
      showToast(t('admin.entitySeo.saveSuccess'), 'success')
    } catch {
      showToast(t('common.error.serverError'), 'error')
    }
  }

  return (
    <section className="rounded-xl border border-border bg-surface p-4">
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.entitySeo.title')}
        </Text>

        <Stack gap="xs">
          <label htmlFor="entity-meta-title" className="text-sm font-medium text-text-primary">
            {t('admin.entitySeo.metaTitle')}
          </label>
          <input
            id="entity-meta-title"
            type="text"
            value={metaTitle}
            onChange={(e) => {
              setMetaTitle(e.target.value)
            }}
            placeholder={t('admin.entitySeo.metaTitle.placeholder')}
            maxLength={255}
            className="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </Stack>

        <Stack gap="xs">
          <label
            htmlFor="entity-meta-description"
            className="text-sm font-medium text-text-primary"
          >
            {t('admin.entitySeo.metaDescription')}
          </label>
          <textarea
            id="entity-meta-description"
            value={metaDescription}
            onChange={(e) => {
              setMetaDescription(e.target.value)
            }}
            placeholder={t('admin.entitySeo.metaDescription.placeholder')}
            rows={3}
            className="w-full rounded-md border border-border bg-surface px-3 py-2 text-sm text-text-primary placeholder:text-text-muted focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </Stack>

        <div className="flex items-center gap-inline-sm">
          <Button
            variant="secondary"
            size="sm"
            disabled={updateMutation.isPending}
            onClick={() => void save()}
          >
            {updateMutation.isPending ? t('admin.entitySeo.saving') : t('admin.entitySeo.save')}
          </Button>
        </div>
      </Stack>
    </section>
  )
}

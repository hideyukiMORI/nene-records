import { useState } from 'react'
import {
  useWxrImport,
  useWxrPreview,
  type WxrImportPlanDto,
  type WxrImportResultDto,
} from '@/entities/wxr-import'
import { useTranslation } from '@/shared/i18n'
import { Button, Card, Stack, Text } from '@/shared/ui'

function ErrorAlert({ message }: { message: string }) {
  return (
    <div
      role="alert"
      className="rounded-sm border border-danger bg-danger/10 px-inline-sm py-stack-xs text-caption font-medium text-danger"
    >
      {message}
    </div>
  )
}

function Stat({ label, value }: { label: string; value: number }) {
  return (
    <div className="flex flex-col gap-stack-3xs rounded-sm border border-border-subtle px-inline-md py-stack-xs">
      <span className="font-chrome text-tiny uppercase tracking-wide text-text-muted">{label}</span>
      <span className="font-sans text-heading-sm font-semibold text-text-primary">{value}</span>
    </div>
  )
}

function PlanCard({ plan }: { plan: WxrImportPlanDto }) {
  const { t } = useTranslation()

  return (
    <Card>
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.import.wxr.planTitle')}
        </Text>
        <div className="flex flex-wrap gap-inline-sm">
          <Stat label={t('admin.import.wxr.planned')} value={plan.planned_count} />
          <Stat label={t('admin.import.wxr.skipped')} value={plan.skipped_count} />
          <Stat label={t('admin.import.wxr.tags')} value={plan.tags.length} />
        </div>
        {plan.planned.length > 0 ? (
          <ul className="flex flex-col gap-stack-3xs">
            {plan.planned.map((item) => (
              <li
                key={`${item.entity_type}/${item.slug}`}
                className="text-caption text-text-secondary"
              >
                <span className="font-medium text-text-primary">{item.title || item.slug}</span>
                {` — ${item.entity_type} · ${item.status}`}
                {item.tags.length > 0 ? ` · ${item.tags.join(', ')}` : ''}
              </li>
            ))}
          </ul>
        ) : null}
        {plan.skipped.length > 0 ? (
          <Stack gap="xs">
            <Text variant="caption" muted>
              {t('admin.import.wxr.skipped')}
            </Text>
            <ul className="flex flex-col gap-stack-3xs">
              {plan.skipped.map((item, index) => (
                <li key={`${item.title}-${String(index)}`} className="text-caption text-text-muted">
                  {item.title} — {item.reason}
                </li>
              ))}
            </ul>
          </Stack>
        ) : null}
        {plan.warnings.length > 0 ? (
          <Stack gap="xs">
            <Text variant="caption" muted>
              {t('admin.import.wxr.warnings')}
            </Text>
            <ul className="flex flex-col gap-stack-3xs">
              {plan.warnings.map((warning, index) => (
                <li key={String(index)} className="text-caption text-warn">
                  {warning}
                </li>
              ))}
            </ul>
          </Stack>
        ) : null}
      </Stack>
    </Card>
  )
}

function ResultCard({ result }: { result: WxrImportResultDto }) {
  const { t } = useTranslation()

  return (
    <Card>
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {t('admin.import.wxr.resultTitle')}
        </Text>
        <div className="flex flex-wrap gap-inline-sm">
          <Stat label={t('admin.import.wxr.created')} value={result.created_entities} />
          <Stat label={t('admin.import.wxr.skippedExisting')} value={result.skipped_existing} />
          <Stat label={t('admin.import.wxr.tagsEnsured')} value={result.tags_ensured} />
          <Stat label={t('admin.import.wxr.tagLinks')} value={result.tag_links} />
          <Stat label={t('admin.import.wxr.redirects')} value={result.redirects_created} />
          <Stat label={t('admin.import.wxr.media')} value={result.media_imported} />
        </div>
        <Text muted>{t('admin.import.wxr.done')}</Text>
      </Stack>
    </Card>
  )
}

export function WxrImportView() {
  const { t } = useTranslation()
  const [file, setFile] = useState<File | null>(null)
  const preview = useWxrPreview()
  const importMutation = useWxrImport()

  return (
    <Stack gap="lg">
      <Card>
        <Stack gap="md">
          <Text as="h2" variant="heading-sm">
            {t('admin.import.wxr.title')}
          </Text>
          <Text muted>{t('admin.import.wxr.description')}</Text>
          <label className="flex flex-col gap-stack-xs">
            <span className="font-sans text-body font-medium text-text-primary">
              {t('admin.import.wxr.fileLabel')}
            </span>
            <input
              type="file"
              accept=".xml,application/xml,text/xml"
              className="text-caption text-text-secondary"
              onChange={(event) => {
                setFile(event.target.files?.[0] ?? null)
                preview.reset()
                importMutation.reset()
              }}
            />
          </label>
          <div className="flex items-center gap-inline-sm">
            <Button
              type="button"
              disabled={file === null || preview.isPending}
              onClick={() => {
                if (file !== null) {
                  preview.mutate(file)
                }
              }}
            >
              {preview.isPending ? t('admin.import.wxr.previewing') : t('admin.import.wxr.preview')}
            </Button>
            {preview.data !== undefined ? (
              <Button
                type="button"
                variant="secondary"
                disabled={file === null || importMutation.isPending}
                onClick={() => {
                  if (file !== null) {
                    importMutation.mutate(file)
                  }
                }}
              >
                {importMutation.isPending
                  ? t('admin.import.wxr.importing')
                  : t('admin.import.wxr.runImport')}
              </Button>
            ) : null}
          </div>
          {preview.error !== null ? <ErrorAlert message={preview.error.title} /> : null}
          {importMutation.error !== null ? (
            <ErrorAlert message={importMutation.error.title} />
          ) : null}
        </Stack>
      </Card>

      {preview.data !== undefined && importMutation.data === undefined ? (
        <PlanCard plan={preview.data} />
      ) : null}
      {importMutation.data !== undefined ? <ResultCard result={importMutation.data} /> : null}
    </Stack>
  )
}

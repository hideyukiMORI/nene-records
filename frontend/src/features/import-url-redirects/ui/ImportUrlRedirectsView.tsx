import { useState } from 'react'
import {
  useRedirectCsvImport,
  useRedirectCsvPreview,
  type RedirectCsvImportDto,
} from '@/entities/redirect-import'
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

function ResultCard({ result }: { result: RedirectCsvImportDto }) {
  const { t } = useTranslation()
  const isPreview = result.mode === 'preview'

  return (
    <Card>
      <Stack gap="md">
        <Text as="h2" variant="heading-sm">
          {isPreview ? t('admin.import.csv.previewTitle') : t('admin.import.csv.resultTitle')}
        </Text>
        <div className="flex flex-wrap gap-inline-sm">
          <Stat label={t('admin.import.csv.valid')} value={result.valid_rows} />
          {isPreview ? null : (
            <Stat label={t('admin.import.csv.imported')} value={result.imported_rows} />
          )}
          <Stat label={t('admin.import.csv.skipped')} value={result.skipped_rows} />
        </div>
        {result.samples.length > 0 ? (
          <Stack gap="xs">
            <Text variant="caption" muted>
              {t('admin.import.csv.samples')}
            </Text>
            <ul className="flex flex-col gap-stack-3xs">
              {result.samples.map((sample) => (
                <li key={sample.source} className="text-caption text-text-secondary">
                  <code className="text-text-primary">{sample.source}</code>
                  {' → '}
                  <code className="text-text-primary">{sample.target}</code>
                </li>
              ))}
            </ul>
          </Stack>
        ) : null}
        {result.errors.length > 0 ? (
          <Stack gap="xs">
            <Text variant="caption" muted>
              {t('admin.import.csv.errors')}
            </Text>
            <ul className="flex flex-col gap-stack-3xs">
              {result.errors.map((error, index) => (
                <li
                  key={`${String(error.line)}-${String(index)}`}
                  className="text-caption text-warn"
                >
                  {t('admin.import.csv.errorLine', { line: error.line })} — {error.message}
                </li>
              ))}
            </ul>
          </Stack>
        ) : null}
        {isPreview ? null : <Text muted>{t('admin.import.csv.done')}</Text>}
      </Stack>
    </Card>
  )
}

export function ImportUrlRedirectsView() {
  const { t } = useTranslation()
  const [file, setFile] = useState<File | null>(null)
  const preview = useRedirectCsvPreview()
  const importMutation = useRedirectCsvImport()

  return (
    <Stack gap="lg">
      <Card>
        <Stack gap="md">
          <Text as="h2" variant="heading-sm">
            {t('admin.import.csv.title')}
          </Text>
          <Text muted>{t('admin.import.csv.description')}</Text>
          <label className="flex flex-col gap-stack-xs">
            <span className="font-sans text-body font-medium text-text-primary">
              {t('admin.import.csv.fileLabel')}
            </span>
            <input
              type="file"
              accept=".csv,text/csv"
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
              {preview.isPending ? t('admin.import.csv.previewing') : t('admin.import.csv.preview')}
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
                  ? t('admin.import.csv.importing')
                  : t('admin.import.csv.runImport')}
              </Button>
            ) : null}
          </div>
          {preview.error !== null ? <ErrorAlert message={preview.error.title} /> : null}
          {importMutation.error !== null ? (
            <ErrorAlert message={importMutation.error.title} />
          ) : null}
        </Stack>
      </Card>

      {importMutation.data !== undefined ? (
        <ResultCard result={importMutation.data} />
      ) : preview.data !== undefined ? (
        <ResultCard result={preview.data} />
      ) : null}
    </Stack>
  )
}

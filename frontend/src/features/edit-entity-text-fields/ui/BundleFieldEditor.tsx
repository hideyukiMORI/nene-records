import { useTranslation } from '@/shared/i18n'
import { Textarea } from '@/shared/ui'
import { parseBundleDocument, serializeBundleDocument } from '@/shared/lib/bundle-document'

export interface BundleFieldEditorProps {
  id: string
  label: string
  /** The stored field value: a JSON envelope { html, seoText }. */
  value: string
  disabled: boolean
  onChange: (value: string) => void
}

/**
 * Editor for a `bundle` field (#311 / #491 WS3-S3a): a raw HTML/JS/CSS document
 * (rendered only inside a sandboxed iframe on the public site) plus a REQUIRED
 * crawlable `seoText` markdown twin (dual representation — no cloaking; it is
 * also the no-JS / a11y fallback). Admin never executes the HTML.
 */
export function BundleFieldEditor({
  id,
  label,
  value,
  disabled,
  onChange,
}: BundleFieldEditorProps) {
  const { t } = useTranslation()
  const doc = parseBundleDocument(value)
  const patch = (next: Partial<{ html: string; seoText: string }>) => {
    onChange(serializeBundleDocument({ ...doc, ...next }))
  }

  return (
    <div className="flex flex-col gap-stack-sm">
      <span className="font-sans text-body font-medium text-text-primary">{label}</span>
      <span className="font-sans text-caption text-text-muted">
        {t('admin.fieldDefs.bundle.hint')}
      </span>

      <label
        htmlFor={`${id}-html`}
        className="font-sans text-caption font-medium text-text-primary"
      >
        {t('admin.bundle.htmlLabel')}
      </label>
      <Textarea
        id={`${id}-html`}
        rows={16}
        size="sm"
        mono
        disabled={disabled}
        value={doc.html}
        onChange={(event) => {
          patch({ html: event.target.value })
        }}
      />

      <label htmlFor={`${id}-seo`} className="font-sans text-caption font-medium text-text-primary">
        {t('admin.bundle.seoLabel')}
      </label>
      <Textarea
        id={`${id}-seo`}
        rows={6}
        size="sm"
        disabled={disabled}
        value={doc.seoText}
        onChange={(event) => {
          patch({ seoText: event.target.value })
        }}
      />
      <span className="font-sans text-caption text-text-muted">{t('admin.bundle.seoHint')}</span>
    </div>
  )
}

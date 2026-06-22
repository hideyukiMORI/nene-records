import { useTranslation } from '@/shared/i18n'
import { parseBundleDocument, serializeBundleDocument } from '@/shared/lib/bundle-document'

const TEXTAREA_CLASS =
  'rounded-sm border border-border bg-surface-raised px-inline-sm py-stack-xs text-caption text-text-primary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent'

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
      <textarea
        id={`${id}-html`}
        rows={16}
        disabled={disabled}
        value={doc.html}
        onChange={(event) => {
          patch({ html: event.target.value })
        }}
        className={`${TEXTAREA_CLASS} font-mono`}
      />

      <label htmlFor={`${id}-seo`} className="font-sans text-caption font-medium text-text-primary">
        {t('admin.bundle.seoLabel')}
      </label>
      <textarea
        id={`${id}-seo`}
        rows={6}
        disabled={disabled}
        value={doc.seoText}
        onChange={(event) => {
          patch({ seoText: event.target.value })
        }}
        className={`${TEXTAREA_CLASS} font-sans`}
      />
      <span className="font-sans text-caption text-text-muted">{t('admin.bundle.seoHint')}</span>
    </div>
  )
}

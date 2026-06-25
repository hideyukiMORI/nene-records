import { BlocksRenderer } from '@/shared/ui/blocks'
// The canonical public block styles (`.nene-public`-scoped, so they cannot leak
// into the admin) plus the default consumer theme tokens (`[data-theme]`-scoped).
// Reusing them renders the editor preview exactly as the public page would,
// rather than re-implementing block layout for the admin.
import '@/pages/consumer/public-site.css'
import '@/shared/ui/theme/themes/consumer-brand.css'

interface BlocksPreviewProps {
  /** The live blocks document (JSON string) from the editor. */
  documentJson: string
}

/**
 * Live preview of a `blocks` field (#538): renders the editor's current document
 * through the same {@see BlocksRenderer} the public site uses, inside a
 * `.nene-public` scope so the public CSS + default consumer theme tokens apply.
 * The exact active theme isn't resolved here (default consumer) — structure,
 * layout and type are what the editor needs to see while composing.
 */
export function BlocksPreview({ documentJson }: BlocksPreviewProps) {
  return (
    <div
      className="nene-public overflow-auto rounded-md"
      data-theme="consumer"
      data-theme-mode="light"
      style={{ maxHeight: '70vh' }}
    >
      <BlocksRenderer documentJson={documentJson} />
    </div>
  )
}

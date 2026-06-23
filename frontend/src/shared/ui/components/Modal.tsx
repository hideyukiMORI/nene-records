import { type ReactNode, useEffect } from 'react'

export interface ModalProps {
  /** Called on backdrop click and on Escape. */
  onClose: () => void
  /** Accessible label for the backdrop close button. */
  closeLabel: string
  /** id of the title element inside `children`, wired to `aria-labelledby`. */
  labelledBy?: string
  /** Panel classes — caller supplies max-width / layout / shadow (e.g. `max-w-md shadow-md`). */
  panelClassName?: string
  /** Overlay container classes (defaults to the standard modal padding). */
  className?: string
  children: ReactNode
}

/**
 * Overlay + centered dialog panel shared by the app's modals (confirm dialogs,
 * the media picker, the layout tour). Closes on backdrop click and Escape; the
 * caller owns mounting (render it only when open) and the panel's contents.
 *
 * In:  onClose, closeLabel, labelledBy, panelClassName, className, children
 * Out: onClose() (backdrop click / Escape)
 *
 * Does not: fetch data, own open state, or know entity ids.
 */
export function Modal({
  onClose,
  closeLabel,
  labelledBy,
  panelClassName,
  className = 'px-inline-md py-stack-md',
  children,
}: ModalProps) {
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose()
      }
    }
    document.addEventListener('keydown', handleKeyDown)
    return () => {
      document.removeEventListener('keydown', handleKeyDown)
    }
  }, [onClose])

  return (
    <div className={`fixed inset-0 z-modal flex items-center justify-center ${className}`}>
      <button
        type="button"
        aria-label={closeLabel}
        className="absolute inset-0 bg-surface-overlay/80"
        onClick={onClose}
      />
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby={labelledBy}
        className={`relative w-full rounded-md border border-border bg-surface-raised p-inline-lg ${
          panelClassName ?? ''
        }`}
      >
        {children}
      </div>
    </div>
  )
}

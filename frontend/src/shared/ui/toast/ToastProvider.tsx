import { useCallback, useRef, useState } from 'react'
import { ToastContext } from './toast-context'
import type { Toast, ToastType } from './toast-context'

// ── Icons ────────────────────────────────────────────────────────────────────

function IconCheck() {
  return (
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
      <path
        d="M3 8l3.5 3.5L13 4.5"
        stroke="currentColor"
        strokeWidth="1.75"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

function IconAlertCircle() {
  return (
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
      <circle cx="8" cy="8" r="6.5" stroke="currentColor" strokeWidth="1.5" />
      <path d="M8 5v4" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" />
      <circle cx="8" cy="11" r="0.75" fill="currentColor" />
    </svg>
  )
}

function IconInfo() {
  return (
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
      <circle cx="8" cy="8" r="6.5" stroke="currentColor" strokeWidth="1.5" />
      <path d="M8 7v4" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" />
      <circle cx="8" cy="5" r="0.75" fill="currentColor" />
    </svg>
  )
}

// ── Single toast item ─────────────────────────────────────────────────────────

const TYPE_STYLES: Record<
  ToastType,
  { container: string; icon: string; iconNode: React.ReactNode }
> = {
  success: {
    container: 'bg-surface-raised border border-border text-text-primary shadow-lg',
    icon: 'text-green-500',
    iconNode: <IconCheck />,
  },
  error: {
    container: 'bg-surface-raised border border-red-500/40 text-text-primary shadow-lg',
    icon: 'text-red-400',
    iconNode: <IconAlertCircle />,
  },
  info: {
    container: 'bg-surface-raised border border-border text-text-primary shadow-lg',
    icon: 'text-accent',
    iconNode: <IconInfo />,
  },
}

function ToastItem({ toast, onDismiss }: { toast: Toast; onDismiss: (id: number) => void }) {
  const styles = TYPE_STYLES[toast.type]
  return (
    <div
      role="status"
      aria-live="polite"
      className={[
        'flex items-start gap-2.5 rounded-lg px-4 py-3 font-sans text-body transition-all duration-300',
        styles.container,
        toast.fading ? 'translate-x-2 opacity-0' : 'translate-x-0 opacity-100',
      ].join(' ')}
    >
      <span className={['mt-0.5 shrink-0', styles.icon].join(' ')}>{styles.iconNode}</span>
      <span className="flex-1 leading-snug">{toast.message}</span>
      <button
        type="button"
        onClick={() => {
          onDismiss(toast.id)
        }}
        aria-label="閉じる"
        className="ml-1 shrink-0 text-text-muted transition-colors hover:text-text-primary"
      >
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
          <path
            d="M3 3l8 8M11 3L3 11"
            stroke="currentColor"
            strokeWidth="1.5"
            strokeLinecap="round"
          />
        </svg>
      </button>
    </div>
  )
}

// ── Provider ─────────────────────────────────────────────────────────────────

const DISPLAY_MS = 3000
const FADE_MS = 300

let nextId = 1

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])
  const timersRef = useRef<Map<number, ReturnType<typeof setTimeout>>>(new Map())

  const dismiss = useCallback((id: number) => {
    setToasts((prev) => prev.map((t) => (t.id === id ? { ...t, fading: true } : t)))
    const removeTimer = setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id))
    }, FADE_MS)
    timersRef.current.set(id, removeTimer)
  }, [])

  const showToast = useCallback(
    (message: string, type: ToastType = 'success') => {
      const id = nextId++
      setToasts((prev) => [...prev, { id, message, type, fading: false }])
      const timer = setTimeout(() => {
        dismiss(id)
      }, DISPLAY_MS)
      timersRef.current.set(id, timer)
    },
    [dismiss],
  )

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      {/* ── Toast container (top-right) ── */}
      <div
        aria-label="通知"
        className="pointer-events-none fixed right-4 top-4 z-50 flex flex-col gap-2"
        style={{ minWidth: '18rem', maxWidth: '22rem' }}
      >
        {toasts.map((toast) => (
          <div key={toast.id} className="pointer-events-auto">
            <ToastItem toast={toast} onDismiss={dismiss} />
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}

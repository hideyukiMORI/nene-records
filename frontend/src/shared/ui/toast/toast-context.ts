import { createContext } from 'react'

// ── Types ────────────────────────────────────────────────────────────────────

export type ToastType = 'success' | 'error' | 'info'

export interface Toast {
  id: number
  message: string
  type: ToastType
  /** true = フェードアウト中 */
  fading: boolean
}

export interface ToastContextValue {
  showToast: (message: string, type?: ToastType) => void
}

// ── Context ──────────────────────────────────────────────────────────────────

export const ToastContext = createContext<ToastContextValue>({
  showToast: () => undefined,
})

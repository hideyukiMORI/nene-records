import { createContext, useCallback, useEffect, useState, type ReactNode } from 'react'
import {
  ADMIN_THEME_DEFS,
  canToggleVariant,
  getDataAttr,
  getDefaultVariant,
  type AdminThemeId,
  type ThemeVariant,
} from './admin-theme-config'

export type { AdminThemeId, ThemeVariant }

export interface ThemeContextValue {
  adminThemeId: AdminThemeId
  themeVariant: ThemeVariant
  setAdminTheme: (id: AdminThemeId, variant?: ThemeVariant) => void
  toggleVariant: () => void
  canToggleVariant: boolean
}

const ThemeContext = createContext<ThemeContextValue | null>(null)

const STORAGE_KEY = 'nene-admin-theme'
const LEGACY_STORAGE_KEY = 'nene-theme'

function detectTheme(): { id: AdminThemeId; variant: ThemeVariant } {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored !== null) {
      const lastDash = stored.lastIndexOf('-')
      if (lastDash > 0) {
        const id = stored.slice(0, lastDash) as AdminThemeId
        const variant = stored.slice(lastDash + 1) as ThemeVariant
        const def = ADMIN_THEME_DEFS.find((t) => t.id === id)
        if (def !== undefined && (def.variants as readonly string[]).includes(variant)) {
          return { id, variant }
        }
      }
    }
    // 旧キー (nene-theme) からマイグレーション
    const legacy = localStorage.getItem(LEGACY_STORAGE_KEY)
    if (legacy === 'dark') return { id: 'default', variant: 'dark' }
    if (legacy === 'light') return { id: 'default', variant: 'light' }
  } catch {
    // localStorage blocked
  }
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  return { id: 'default', variant: prefersDark ? 'dark' : 'light' }
}

function applyTheme(id: AdminThemeId, variant: ThemeVariant): void {
  document.documentElement.setAttribute('data-admin-theme', getDataAttr(id, variant))
  document.documentElement.removeAttribute('data-theme')
}

function saveTheme(id: AdminThemeId, variant: ThemeVariant): void {
  try {
    localStorage.setItem(STORAGE_KEY, getDataAttr(id, variant))
  } catch {
    // ignore
  }
}

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [{ id, variant }, setState] = useState<{ id: AdminThemeId; variant: ThemeVariant }>(
    detectTheme,
  )

  const setAdminTheme = useCallback((newId: AdminThemeId, newVariant?: ThemeVariant) => {
    const def = ADMIN_THEME_DEFS.find((t) => t.id === newId)
    const resolvedVariant: ThemeVariant =
      def !== undefined &&
      newVariant !== undefined &&
      (def.variants as readonly string[]).includes(newVariant)
        ? newVariant
        : getDefaultVariant(newId)
    setState({ id: newId, variant: resolvedVariant })
    applyTheme(newId, resolvedVariant)
    saveTheme(newId, resolvedVariant)
  }, [])

  const toggleVariant = useCallback(() => {
    if (!canToggleVariant(id)) return
    const next: ThemeVariant = variant === 'light' ? 'dark' : 'light'
    setState({ id, variant: next })
    applyTheme(id, next)
    saveTheme(id, next)
  }, [id, variant])

  useEffect(() => {
    applyTheme(id, variant)
  }, [id, variant])

  return (
    <ThemeContext.Provider
      value={{
        adminThemeId: id,
        themeVariant: variant,
        setAdminTheme,
        toggleVariant,
        canToggleVariant: canToggleVariant(id),
      }}
    >
      {children}
    </ThemeContext.Provider>
  )
}

export { ThemeContext }
export type { ThemeContextValue }

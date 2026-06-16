import { useCallback, useEffect, useState } from 'react'

/**
 * Public-site color theme controller (light / dark / auto).
 *
 * - `mode` is the user's choice, persisted in localStorage.
 * - `resolvedTheme` is the concrete `[data-theme]` value to apply
 *   (`consumer` | `consumer-dark`); `auto` follows `prefers-color-scheme`.
 *
 * The caller applies `data-theme={resolvedTheme}` and `data-theme-mode={mode}`
 * to its root element. Setting `data-theme-mode` disables the no-JS OS-dark
 * fallback in consumer-brand.css (the controller now owns resolution).
 */
export type ThemeMode = 'light' | 'dark' | 'auto'
export type ConsumerTheme = 'consumer' | 'consumer-dark'

const STORAGE_KEY = 'nene_public_theme_mode'
const DARK_QUERY = '(prefers-color-scheme: dark)'

function prefersDark(): boolean {
  return typeof window !== 'undefined' && typeof window.matchMedia === 'function'
    ? window.matchMedia(DARK_QUERY).matches
    : false
}

function resolveTheme(mode: ThemeMode): ConsumerTheme {
  if (mode === 'auto') {
    return prefersDark() ? 'consumer-dark' : 'consumer'
  }
  return mode === 'dark' ? 'consumer-dark' : 'consumer'
}

function readStoredMode(): ThemeMode {
  if (typeof window === 'undefined') {
    return 'auto'
  }
  const stored = window.localStorage.getItem(STORAGE_KEY)
  return stored === 'light' || stored === 'dark' || stored === 'auto' ? stored : 'auto'
}

export interface ConsumerThemeController {
  mode: ThemeMode
  resolvedTheme: ConsumerTheme
  setMode: (mode: ThemeMode) => void
}

export function useConsumerTheme(): ConsumerThemeController {
  const [mode, setModeState] = useState<ThemeMode>(readStoredMode)
  // Track the OS preference so `auto` can live-update. Initialised synchronously
  // so the first paint already matches the system.
  const [systemDark, setSystemDark] = useState<boolean>(prefersDark)

  const setMode = useCallback((next: ThemeMode) => {
    setModeState(next)
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(STORAGE_KEY, next)
    }
  }, [])

  // Subscribe to OS preference changes; setState happens only in the callback.
  useEffect(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
      return undefined
    }
    const mq = window.matchMedia(DARK_QUERY)
    const onChange = (): void => {
      setSystemDark(mq.matches)
    }
    mq.addEventListener('change', onChange)
    return () => {
      mq.removeEventListener('change', onChange)
    }
  }, [])

  const resolvedTheme: ConsumerTheme =
    mode === 'auto' ? (systemDark ? 'consumer-dark' : 'consumer') : resolveTheme(mode)

  return { mode, resolvedTheme, setMode }
}

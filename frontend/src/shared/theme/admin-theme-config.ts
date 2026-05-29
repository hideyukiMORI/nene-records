export type AdminThemeId = 'default' | 'ubuntu' | 'github' | 'solarized' | 'dracula' | 'monokai'
export type ThemeVariant = 'light' | 'dark'

export interface ThemePreviewColors {
  surface: string
  sidebar: string
  accent: string
}

export interface AdminThemeDef {
  readonly id: AdminThemeId
  readonly name: string
  readonly variants: readonly ThemeVariant[]
  readonly preview: Partial<Record<ThemeVariant, ThemePreviewColors>>
}

export const ADMIN_THEME_DEFS: readonly AdminThemeDef[] = [
  {
    id: 'default',
    name: 'Default',
    variants: ['light', 'dark'],
    preview: {
      light: { surface: '#f7f3ee', sidebar: '#2d3147', accent: '#c8820a' },
      dark: { surface: '#1a1d2e', sidebar: '#10121f', accent: '#d4970e' },
    },
  },
  {
    id: 'ubuntu',
    name: 'Ubuntu',
    variants: ['light', 'dark'],
    preview: {
      light: { surface: '#f6f1ed', sidebar: '#2c0a23', accent: '#e2531d' },
      dark: { surface: '#2a1622', sidebar: '#1c0c17', accent: '#f06a32' },
    },
  },
  {
    id: 'github',
    name: 'GitHub',
    variants: ['light', 'dark'],
    preview: {
      light: { surface: '#fbfcfd', sidebar: '#1f242b', accent: '#1f6feb' },
      dark: { surface: '#14181f', sidebar: '#0b0e13', accent: '#69a7ff' },
    },
  },
  {
    id: 'solarized',
    name: 'Solarized',
    variants: ['light', 'dark'],
    preview: {
      light: { surface: '#fdf6e3', sidebar: '#073642', accent: '#2aa198' },
      dark: { surface: '#002b36', sidebar: '#001f29', accent: '#2aa198' },
    },
  },
  {
    id: 'dracula',
    name: 'Dracula',
    variants: ['dark'],
    preview: {
      dark: { surface: '#282a36', sidebar: '#21222c', accent: '#d896f0' },
    },
  },
  {
    id: 'monokai',
    name: 'Monokai',
    variants: ['dark'],
    preview: {
      dark: { surface: '#272822', sidebar: '#1e1e1c', accent: '#a6e22e' },
    },
  },
] as const

export function getDataAttr(id: AdminThemeId, variant: ThemeVariant): string {
  return `${id}-${variant}`
}

export function getDefaultVariant(id: AdminThemeId): ThemeVariant {
  const def = ADMIN_THEME_DEFS.find((t) => t.id === id)
  if (def?.variants.includes('light')) return 'light'
  return 'dark'
}

export function canToggleVariant(id: AdminThemeId): boolean {
  return (ADMIN_THEME_DEFS.find((t) => t.id === id)?.variants.length ?? 0) > 1
}

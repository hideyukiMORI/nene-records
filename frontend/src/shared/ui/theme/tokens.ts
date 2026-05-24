/** Read-only mirrors of theme CSS variables for programmatic use. */
export const themeTokens = {
  color: {
    surface: 'var(--color-surface)',
    surfaceRaised: 'var(--color-surface-raised)',
    textPrimary: 'var(--color-text-primary)',
    textMuted: 'var(--color-text-muted)',
    accent: 'var(--color-accent)',
    danger: 'var(--color-danger)',
  },
  spacing: {
    inlineMd: 'var(--spacing-inline-md)',
    stackMd: 'var(--spacing-stack-md)',
  },
} as const

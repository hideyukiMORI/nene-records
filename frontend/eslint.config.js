import js from '@eslint/js'
import nene2 from '@hideyukimori/nene2-standards'
import eslintConfigPrettier from 'eslint-config-prettier'
import importPlugin from 'eslint-plugin-import'
import jsxA11y from 'eslint-plugin-jsx-a11y'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import globals from 'globals'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tseslint from 'typescript-eslint'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

const entityInternalFiles = [
  './src/entities/*/api-types.ts',
  './src/entities/*/mapper.ts',
  './src/entities/*/queries.ts',
  './src/entities/*/mutations.ts',
  './src/entities/*/query-keys.ts',
  './src/entities/*/ids.ts',
  './src/entities/*/model.ts',
  './src/entities/*/enum.ts',
]

const importZones = [
  {
    target: './src/features',
    from: entityInternalFiles,
  },
  {
    target: './src/features',
    from: './src/shared/api',
  },
  {
    target: './src/features',
    from: './src/shared/api/generated',
  },
  {
    target: './src/pages',
    from: entityInternalFiles,
  },
  {
    target: './src/pages',
    from: './src/shared/api',
  },
  {
    target: './src/shared/ui',
    from: './src/entities',
  },
  // shared/theme is pure token/design infra — it must never reach up into entities.
  // (shared/api and shared/lib still hold a few load-bearing entity deps — the
  // API client's auth store and the public-record domain helpers/bootstrap seed —
  // whose relocation is tracked separately, so they are not zoned here yet.)
  {
    target: './src/shared/theme',
    from: './src/entities',
  },
  {
    target: './src/shared/ui',
    from: './src/features',
  },
  {
    target: './src/shared/ui',
    from: './src/shared/api',
  },
  {
    target: './src/features/public-browse-index',
    from: './src/features/manage-*',
  },
  {
    target: './src/features/public-browse-entity-records',
    from: './src/features/manage-*',
  },
  {
    target: './src/features/public-view-entity-record',
    from: './src/features/manage-*',
  },
]

const noArbitraryTailwindValues = {
  selector: 'JSXAttribute[name.name="className"] Literal[value=/\\[.*\\]/]',
  message: 'Tailwind arbitrary values are forbidden outside shared/ui/theme.',
}

// Recurrence guards from the #507 audit remediation. Both currently have zero
// violations; they stop the migrated patterns from creeping back.
//  - WS-08: raw Tailwind palette colours must be semantic theme tokens.
//  - WS-10/WS-11: user-facing Japanese must go through i18n (features/pages only).
const noRawTailwindPalette = {
  selector:
    'Literal[value=/\\b(?:bg|text|border|ring|fill|stroke|from|via|to|divide|outline|decoration|shadow|caret|accent)-(?:red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|neutral|stone)-\\d{2,3}\\b/]',
  message:
    'Raw Tailwind palette colours are forbidden — use semantic theme tokens (text-danger, bg-success-weak, text-warning, bg-scrim, …) from src/shared/ui/theme/themes/default.css @theme.',
}

const noHardcodedJapanese = {
  selector: 'Literal[value=/[\\u3040-\\u30FF\\u3400-\\u9FFF]/]',
  message:
    'Hardcoded Japanese string in a feature/page — route user-facing text through useTranslation()/t(). Non-translatable native endonyms may use an inline eslint-disable with a reason.',
}

export default tseslint.config(
  {
    ignores: [
      'dist',
      'storybook-static',
      'node_modules',
      'coverage',
      'src/shared/api/schema.gen.ts',
      'public/mockServiceWorker.js',
    ],
  },
  {
    extends: [js.configs.recommended, ...tseslint.configs.strictTypeChecked],
    files: ['src/**/*.{ts,tsx}', 'tests/**/*.{ts,tsx}'],
    languageOptions: {
      ecmaVersion: 2023,
      globals: globals.browser,
      parserOptions: {
        project: ['./tsconfig.app.json'],
        tsconfigRootDir: __dirname,
      },
    },
    plugins: {
      'react-hooks': reactHooks,
      'react-refresh': reactRefresh,
      'jsx-a11y': jsxA11y,
      import: importPlugin,
    },
    settings: {
      'import/resolver': {
        typescript: {
          project: './tsconfig.app.json',
        },
      },
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
      ...jsxA11y.configs.recommended.rules,
      'import/no-restricted-paths': ['error', { zones: importZones }],
      'no-restricted-syntax': ['error', noArbitraryTailwindValues, noRawTailwindPalette],
    },
  },
  {
    // Feature/page components must use semantic tokens + i18n (audit #507 recurrence
    // guards). Tests and stories are exempt: JA assertions / demo content are fine.
    files: ['src/features/**/*.{ts,tsx}', 'src/pages/**/*.{ts,tsx}'],
    ignores: ['**/*.test.{ts,tsx}', '**/*.stories.{ts,tsx}'],
    rules: {
      'no-restricted-syntax': [
        'error',
        noArbitraryTailwindValues,
        noRawTailwindPalette,
        noHardcodedJapanese,
      ],
    },
  },
  {
    files: ['**/*.test.ts', '**/*.test.tsx'],
    rules: {
      '@typescript-eslint/no-unsafe-call': 'off',
      '@typescript-eslint/no-unsafe-member-access': 'off',
      '@typescript-eslint/no-unsafe-argument': 'off',
      '@typescript-eslint/no-unsafe-assignment': 'off',
    },
  },
  {
    files: ['.storybook/**/*.{ts,tsx}', 'vitest.config.ts'],
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    languageOptions: {
      ecmaVersion: 2023,
      globals: globals.browser,
    },
    plugins: {
      'react-refresh': reactRefresh,
    },
    rules: {
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
    },
  },
  eslintConfigPrettier,
  // 公認差異 records-cookie-auth（HttpOnly cookie＋X-Requested-With CSRF）の実行可能登録。
  // 正本: nene2-fleet-tooling registries/fleet.jsonc（規約 01 §7-1 / 02 §11 AU-2・会議 R1④⑨）。
  // gate-integrity は name `nene2/overrides/records-cookie-auth` で適用有無を照合する。
  // 現時点の実体は marker config（緩和ルールなし — A-7 token store 検査の配布後に差し替え座席になる）。
  ...nene2.overrides.recordsCookieAuth,
)

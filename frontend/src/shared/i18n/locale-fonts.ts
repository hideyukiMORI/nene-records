import type { SupportedLocale } from './locales'

/**
 * CSS カスタムプロパティ名。
 * Tailwind v4 は font-sans ユーティリティに --font-sans を使う（--font-family-sans ではない）。
 * document.documentElement に inline style で上書きすることで @layer theme 宣言を差し替える。
 */
export const ADMIN_FONT_FAMILY_VAR = '--font-sans'

/**
 * ロケール別 UI フォントスタック。
 * Latin 系: Inter（ビジネス向け欧文サンセリフ）
 * 日本語: Noto Sans JP（Google 推奨、ゴシック系で視認性高い）
 * 中国語簡体字: Noto Sans SC（CJK 統一デザイン）
 * フォント名は @fontsource パッケージ名と合わせる。
 */
export const LOCALE_FONT_STACKS: Record<SupportedLocale, string> = {
  en: '"Inter", ui-sans-serif, system-ui, sans-serif',
  ja: '"Noto Sans JP", "Hiragino Sans", "Yu Gothic UI", sans-serif',
  fr: '"Inter", ui-sans-serif, system-ui, sans-serif',
  'zh-Hans': '"Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif',
  'pt-BR': '"Inter", ui-sans-serif, system-ui, sans-serif',
  de: '"Inter", ui-sans-serif, system-ui, sans-serif',
}

export function getLocaleFontStack(locale: SupportedLocale): string {
  return LOCALE_FONT_STACKS[locale]
}

/**
 * `document.documentElement` に CSS 変数を設定してフォントを切り替える。
 * インラインスタイルはシートより優先されるため @theme 宣言を上書きできる。
 */
export function applyLocaleFontFamily(
  locale: SupportedLocale,
  root: HTMLElement = document.documentElement,
): void {
  root.style.setProperty(ADMIN_FONT_FAMILY_VAR, getLocaleFontStack(locale))
}

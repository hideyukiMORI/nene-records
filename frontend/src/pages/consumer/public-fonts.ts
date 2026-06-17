/**
 * 公開サイト用フォント (@fontsource 同梱 = セルフホスト・外部リクエストなし)。
 * `PublicShell` からのみインポートし、admin バンドル (`src/fonts.ts`) には混ぜない。
 *
 * Source Serif 4: 「Reading」「Botanical」「Newsprint」テーマの serif 見出し/本文。
 * Bricolage Grotesque: 「Terracotta」(consumer) リデザインの display/chrome。
 * 許可リスト外フォントはテーマ契約どおりここにセルフホスト同梱する（外部 @import 不可）。
 * Source Serif 700 は Newsprint の太い見出し用。
 * Aurora / Pastel / Noir が使う Inter / Saira / Space Grotesk / JetBrains は
 * admin の fonts.ts 経由で読まれる。
 */
import '@fontsource/source-serif-4/latin-400.css'
import '@fontsource/source-serif-4/latin-400-italic.css'
import '@fontsource/source-serif-4/latin-600.css'
import '@fontsource/source-serif-4/latin-700.css'
import '@fontsource/bricolage-grotesque/500.css'
import '@fontsource/bricolage-grotesque/600.css'
import '@fontsource/bricolage-grotesque/700.css'
import '@fontsource/bricolage-grotesque/800.css'

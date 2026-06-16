/**
 * 公開サイト用フォント (@fontsource 同梱 = セルフホスト・外部リクエストなし)。
 * `PublicShell` からのみインポートし、admin バンドル (`src/fonts.ts`) には混ぜない。
 *
 * Source Serif 4: 「Reading」テーマの serif 本文。許可リスト外フォントは
 * テーマ契約どおりここにセルフホスト同梱する（外部 @import 不可）。
 * 既定テーマ (consumer / aurora) が使う Inter / Saira / Space Grotesk /
 * JetBrains は現状 admin の fonts.ts 経由で読まれる。
 */
import '@fontsource/source-serif-4/latin-400.css'
import '@fontsource/source-serif-4/latin-400-italic.css'
import '@fontsource/source-serif-4/latin-600.css'

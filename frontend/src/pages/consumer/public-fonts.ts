/**
 * 公開サイト用フォント (@fontsource 同梱 = セルフホスト・外部リクエストなし)。
 * `PublicShell` からのみインポートし、admin バンドル (`src/fonts.ts`) には混ぜない。
 *
 * テーマ固有の serif/display フォントをセルフホスト同梱する（外部 @import 不可）。
 * - Source Serif 4: Reading / Botanical / Newsprint / Academia（700 は太見出し）。
 * - Bricolage Grotesque: Terracotta(consumer) の display/chrome。
 * - batch-10（#367）: Playfair Display(luxe) / Archivo(swiss) / Orbitron(synthwave)
 *   / Fredoka(funk) / Nunito(coastal) / Nunito Sans(japandi)。
 * Inter / Saira / Space Grotesk / JetBrains は admin の fonts.ts 経由で読まれる。
 */
import '@fontsource/source-serif-4/latin-400.css'
import '@fontsource/source-serif-4/latin-400-italic.css'
import '@fontsource/source-serif-4/latin-600.css'
import '@fontsource/source-serif-4/latin-700.css'
import '@fontsource/bricolage-grotesque/500.css'
import '@fontsource/bricolage-grotesque/600.css'
import '@fontsource/bricolage-grotesque/700.css'
import '@fontsource/bricolage-grotesque/800.css'
// batch-10 display faces
import '@fontsource/playfair-display/600.css'
import '@fontsource/playfair-display/700.css'
import '@fontsource/playfair-display/900.css'
import '@fontsource/archivo/600.css'
import '@fontsource/archivo/700.css'
import '@fontsource/archivo/800.css'
import '@fontsource/orbitron/500.css'
import '@fontsource/orbitron/700.css'
import '@fontsource/orbitron/800.css'
import '@fontsource/fredoka/500.css'
import '@fontsource/fredoka/600.css'
import '@fontsource/fredoka/700.css'
import '@fontsource/nunito/600.css'
import '@fontsource/nunito/700.css'
import '@fontsource/nunito/800.css'
import '@fontsource/nunito-sans/300.css'
import '@fontsource/nunito-sans/400.css'
import '@fontsource/nunito-sans/600.css'

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
// extra-10 (#367) display faces
import '@fontsource/space-mono/400.css'
import '@fontsource/space-mono/700.css'
import '@fontsource/bangers/400.css'
import '@fontsource/poiret-one/400.css'
import '@fontsource/eb-garamond/400.css'
import '@fontsource/eb-garamond/500.css'
import '@fontsource/eb-garamond/600.css'
import '@fontsource/unifrakturcook/700.css'
import '@fontsource/oswald/500.css'
import '@fontsource/oswald/600.css'
import '@fontsource/oswald/700.css'
import '@fontsource/saira-condensed/600.css'
import '@fontsource/saira-condensed/700.css'
import '@fontsource/saira-condensed/800.css'
import '@fontsource/rye/400.css'
import '@fontsource/varela-round/400.css'
// Shippori Mincho (sumi) — latin + japanese subsets for the JP serif voice
import '@fontsource/shippori-mincho/400.css'
import '@fontsource/shippori-mincho/600.css'
import '@fontsource/shippori-mincho/700.css'
import '@fontsource/shippori-mincho/japanese-400.css'
import '@fontsource/shippori-mincho/japanese-600.css'
import '@fontsource/shippori-mincho/japanese-700.css'

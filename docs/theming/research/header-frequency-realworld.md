# ヘッダー頻度調査 — 実サイト横断サンプリング

> 調査: #419 Phase B のプリセット選定向け。`header-patterns.md` §2/§5 のモデルへ正規化。
> 手法: メインスレッドの WebFetch で各サイトのトップページを取得し、ヘッダー構造を分類。
> 注意（spec §11）: ライブサイトは JS 描画＋クリーン化 markdown のため構造復元に限界。判定不能・取得拒否は除外し、確信できた **10 サイト**を一次集計の母数とする。

## 1. 取得状況

- **確信サンプル（n=10）**: stripe, vercel, shopify, squarespace（SaaS/product）/ smashingmagazine, css-tricks（blog/media）/ allbirds, gymshark（ecommerce）/ pentagram（portfolio/agency）/ toyota（corporate）。
- **取得拒否（anti-bot / 403、除外）**: theguardian, bbc, apnews, ibm。news 系は bot 対策で軒並み拒否されたため、news vertical は弱い。

## 2. 骨格分布（n=10）

| 骨格 | 件数 | 該当 |
| --- | --- | --- |
| `classic`（brand左・nav・独立アクション/CTA右） | 7 | stripe, vercel, shopify, squarespace, allbirds, gymshark, toyota |
| `nav-right`（brand左・nav＋検索を右に集約） | 3 | smashingmagazine, css-tricks, pentagram |
| `centered` / 2行系 | 0 | — |

→ **10/10 が「brand左・1行」**。`classic` 優勢。WPパターン調査（classic 50% + nav-right 45% = 95%）と完全に整合。

## 3. 要素出現率（n=10）

| 要素 | 出現 | 偏り |
| --- | --- | --- |
| Top バー（告知/送料/言語） | 3（30%） | **ecommerce・SaaSトライアルに集中**（allbirds/gymshark/squarespace） |
| 検索 | 5（50%） | **コンテンツ系＋EC**（smashing/css-tricks/allbirds/gymshark/pentagram）。純SaaSはほぼ無し |
| CTA ボタン | 4（40%） | **SaaS/product に集中**（stripe/vercel/shopify/squarespace） |
| カート | 2（20%） | **EC のみ**（allbirds/gymshark） |
| アカウント/ログイン | 6（60%） | SaaS・EC・corporate に広く |
| 言語/地域 | 3（30%） | EC・グローバルSaaS |
| ダークモード切替（ヘッダー内） | 0 | vercel はフッターのみ。**ヘッダーでは皆無** |
| SNS | 1（10%） | portfolio（pentagram）のみ |
| sticky | 判定可は全て yes | shopify/squarespace/pentagram。現代サイトは概ね sticky |
| フル幅 | 10（100%） | boxed ヘッダーは皆無 |

## 4. バーティカル別の既定（プリセット推奨）

| vertical | 推奨骨格 | 既定 ON 要素 | Top バー |
| --- | --- | --- | --- |
| SaaS / product | `classic` | CTA・ログイン | （任意：言語） |
| blog / media | `nav-right` or `classic` | 検索 | なし |
| ecommerce | `classic` | 検索・カート・アカウント・言語 | **あり（告知/送料）** |
| portfolio / agency | `nav-right` / `minimal` | 検索・SNS | なし |
| corporate | `classic` | アカウント・CTA | 任意（連絡先） |

## 5. 結論・実装への含意

1. **`classic` + `nav-right` を二大既定**にすれば実サイトの 100%（本サンプル）・WPパターンの 95% をカバー。
2. **`themeToggle` はヘッダー実物で 0%** → NeNe 独自要素として残すが「業界標準だから」ではない。既定 OFF も検討余地（ただし NeNe のダーク対応は売りなので残置可）。
3. **CTA ボタン（40%）は一級要素**として早期に欲しい（特に SaaS/corporate プリセット）。現 spec §3 では CTA 既定 OFF・将来扱いだが、優先度を上げるべき。
4. **検索は普遍ではない（50%・vertical依存）** → 「検索 always ON」は再考。Phase A の `headerSearch` トグルで site 側が消せる設計は妥当（むしろ vertical プリセットで既定を出し分けたい）。
5. **Top バーは EC/SaaS で 30%** → サニタイズ済み自由テキスト（`infoText`）＋告知用途。Phase C で実装。
6. **2行系（centered-stack/two-row）の実需は本サンプルで 0%** → エンジン構築順は `classic`→`nav-right`→`centered`→（2行系は最後）で正しい。

> 限界: news vertical が bot 拒否で欠落、n=10 と小さい。ただし「brand左・1行・classic優勢」の結論は WPパターン調査（n=22 真ヘッダー）・ビルダー調査（8社）と三者一致しており頑健。

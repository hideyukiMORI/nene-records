# NeNe Records UX 多角検証レポート — WordPress 的体験との比較 × 6ペルソナ

- **作成日**: 2026-06-24
- **対象**: NeNe Records（API-first 多テナント CMS / NENE2 上 / 「WordPress 代替・AI-native・headless」標榜）
- **調査方法（3アプローチ）**:
  1. **コード** — `frontend/src`（81 ルート）/ `src`（40+ ドメイン）/ `openapi.yaml` を横断調査（主要主張は一次コードで裏取り）
  2. **ドキュメント** — README / CLAUDE / AGENTS / roadmap / current.md / 全 handoff / theming・blocks 契約 / ADR / CHANGELOG
  3. **実ブラウジング** — Playwright で seed ログイン（admin/superadmin）し、管理16画面＋公開3画面＋モバイル2画面の計**22スクリーンショット**を取得・目視評価
- **検証基盤（実測）**: PHPUnit 846 / PHPStan lvl8 1156 files / MCP 69 tools / Playwright E2E 220 / frontend 367 tests
- **評価軸**: 6ペルソナ（年齢23–52・男女・6カ国/文化・非技術〜開発者）× 6カテゴリ（とてもいい／やったほうがいい／絶対必要／現状不便／ニーズ増／人気化）

> スクリーンショットは `scratchpad/shots/`（セッション一時領域・01-login〜22-mobile-public-home）に保管。本レポートは未コミット（issue 未紐付け）。

---

## 0. エグゼクティブサマリー

**結論: 「中身（思想・アーキ・ブロック品質・AI-native・運用標準装備）は WordPress を超える部分すらあるのに、"読者に届く最後の1mm"（公開サイトの SEO/SSR）で全ペルソナが採用を止める」** という、惜しさが際立つプロダクト。

### 全ペルソナ共通の最大障害（=最優先で潰すべき1点）
**公開サイトが React SPA（クライアントレンダリング）で、OGタグ・canonical・JSON-LD 構造化データが無く、初期表示が「Loading…/0件」。クローラブルな SSR HTML は別URL `/view/{slug}` に隔離され本番ルートと未統合。** per-entity の SEO メタ（編集画面で入力させている）も SSR に反映されない。

→ **6人中6人がこれを dealbreaker / 最大の減点に挙げた。** 検索流入・SNSシェアのOGPカードは、ブロガー・小売・代理店・報道・デザイナーの全員にとって集客の生命線であり、headless 志向の開発者ですら「WP 代替を名乗るのに付属公開サイトが no-JS で空＝プロダクトの信頼を損なう」と評した。

### 採用判断の分布
| ペルソナ | 判断 | 単一最大の理由 |
| --- | --- | --- |
| 個人ブロガー（JP・28F） | 🔶 条件付き（見送り寄り） | SEO/OGP 欠如で検索・シェアが死ぬ |
| 小売オーナー（NG・45M） | 🔴 見送り | EC（カート/ローカル決済）が無い ＋ OGP 欠如 |
| 代理店ディレクター（MX・34F） | 🔶 条件付き | 公開 SEO/SSR ＋ 公開多言語の未整備 |
| 報道編集者（IE・52M） | 🔶 条件付き（実質見送り） | Google News 不可・OGP 欠如（検索流入が命） |
| 開発者（TW/SG・30M） | 🔶 条件付き | relation 型がスキーマUIで使えない ＋ API トークン不明 |
| デザイン学生（BD/UK・23F） | 🔶 条件付き | SEO/OGP 欠如 ＋ 編集の WYSIWYG が無い |

**誰も「今すぐ採用」しなかった**が、**誰も「永久に pass」とも言わなかった**。全員が「あと数点が揃えば反転する」と明言＝**素材は良く、穴が明確**。

### 反転の鍵（揃えば複数ペルソナが一気に採用側へ）
1. **公開サイトの SEO/SSR/OGP/JSON-LD 標準対応**（6/6 が要求・最優先）
2. **編集のライブプレビュー / WYSIWYG**（ブロック＆テーマ・5/6 が言及）
3. **WordPress からの移行経路（WXR インポート＋301）**（4/6）
4. **AI-native（MCP/ClaudeDesign）を "動くデモ" にして前面化**（差別化の核・全員が将来性として評価）

---

## 1. 6ペルソナ一覧

| # | 名前 | 年齢/性別 | 国・文化 | 職業 | 技術度 | 主な判断軸 | 比較対象 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | 田中 美咲 | 28・F | 日本 | 料理/ライフスタイル個人ブロガー | 非技術 | 映え・ラクさ・写真・SEO・収益化 | WordPress.com |
| 2 | James Okafor | 45・M | ナイジェリア | 家電小売オーナー | 半技術 | 速さ・低データ・EC・コスト | WP + WooCommerce |
| 3 | Sofía Hernández | 34・F | メキシコ | 代理店 Web ディレクター | 半〜技術 | 多テナント・多言語・保守負荷・納品 | 多数の WP |
| 4 | Liam O'Sullivan | 52・M | アイルランド | 地方ニュース編集者 | 半技術 | SEO/Google News・ワークフロー・コメント・法令 | 老朽 WP |
| 5 | 陳 偉（Wei Chen） | 30・M | 台湾/シンガポール | スタートアップ開発者 | 高技術 | 型・API・MCP・self-host・拡張性 | Strapi/Directus/Payload |
| 6 | Aisha Rahman | 23・F | バングラ→英国 | デザイン学生/駆け出し | 中技術 | デザイン自由度・AI・a11y・ダーク・無料 | Framer/Webflow/Wix |

---

## 2. ペルソナ横断マトリクス（誰が何を言ったか）

| 論点 | 1 ブロガー | 2 小売 | 3 代理店 | 4 報道 | 5 開発者 | 6 デザイナー |
| --- | :--: | :--: | :--: | :--: | :--: | :--: |
| **公開 SEO/OGP/SSR/JSON-LD 欠如**（最大障害） | ★必須 | ★必須 | ★必須 | ★必須 | ●減点 | ★必須 |
| 編集のライブプレビュー/WYSIWYG 欠如 | ● | ● | ● | ● | ● | ★必須 |
| WordPress 移行経路（WXR）無し | ★必須 | ★必須 | ★必須 | ★必須 | ● | ○ |
| 設定の生 JSON 露出 | ● | ● | ● | ● | ○ | ● |
| 公開サイト多言語化 未対応 | ○ | ● | ★必須 | ● | – | ● |
| 権限が粗い(3ロール)/2FA/PWリセット無し | ● | ● | ★必須 | ★必須 | ● | – |
| relation 型 UI 未配線 | – | ● | ● | ● | ★必須 | ● |
| EC/決済/フォーム 無し | ○ | ★必須 | ○ | ○ | – | ○ |
| MCP/AI-native（強み・但し未運用） | ◎期待 | ◎期待 | ◎期待 | ◎期待 | ◎強み | ◎夢 |
| 画像最適化/解析/Webhook 標準装備（強み） | ◎ | ◎ | ◎ | ◎ | ◎ | ◎ |
| テーマギャラリー/ブロック描画の美しさ（強み） | ◎ | ◎ | ◎ | ◎ | ◎ | ◎ |

★=採用を左右する必須 / ●=明確な不満 / ◎=高評価 / ○=軽く言及 / –=対象外

**読み取り**: 縦に最も濃いのは **SEO/SSR**（全列★/●）。次いで **WYSIWYG**・**WP移行**・**生JSON**。一方、強み（◎の行）も全列で一致＝**長所への評価は満場一致で、欠点も満場一致**という珍しく明瞭な構図。

---

## 3. 6カテゴリ別 統合所見

### ① とてもいいもの（満場一致の強み）
- **テーマギャラリー（shot 10）** — ミニプレビュー付き多数テーマ＋light/dark。「WP のテーマ選びより今っぽい」（全員）。
- **公開ブロックの描画品質（shot 20）** — hero/callout/columns/**棒・折れ線チャート**/gallery が第一者・任意JS無しで美しく動く。「データ可視化が標準」（デザイナー・開発者・報道）。
- **オンデマンド派生画像（WebP/AVIF/srcset/ETag）** — 「細い回線・データ料金に直撃で効く」（小売）「Lighthouse が勝手に上がる」（デザイナー）。
- **アクセス解析が標準内蔵（shot 02）** — 「WP は有料/プラグイン」（全員）。
- **メディア使用箇所逆引き＋削除ガード（shot 07）** — 事故防止。
- **Webhook（署名/再試行/キュー）＋通知チャネル（slack/discord/chatwork）** — 「プラグイン3-4本ぶんが標準」（代理店）。
- **明示的マルチテナント（DB強制）＋低保守（任意PHP/JS排除）** — 「WP 疲れの根を断つ」（代理店）「SaaS の裏側に欲しい」（開発者）。
- **OpenAPI 真実源＋型 codegen＋MCP 一級市民** — 「Payload 級の DX、MCP は唯一無二」（開発者）。
- **管理画面6言語の高品質 i18n＋モバイル応答（shot 21）** — 「非技術者の私に必須」（ブロガー）。
- **コンテンツタイプ・スターター（article/rich_page/custom_page）** — ゼロからの立ち上げ補助。

### ② やったほうがいいもの（nice-to-have）
- 管理テーマ／ブロック編集の **iframe ライブプレビュー**。
- **リビジョンの diff/復元**（現状ログのみ）。
- **メニューの DnD・ネスト・エンティティ参照リンク**（現状 上下ボタン/自由URL）。
- **画像 crop/rotate・メディア検索/フィルタ**。
- **コメントのスレッド返信・管理者返信UI・スパムキュー**。
- 公式 **TS/JS SDK ＋ openapi-fetch quickstart**、field selection / sparse fieldset、SSE/リアルタイム（開発者）。
- a11y を**可視化**（編集画面に WCAG スコア/コントラスト警告）（デザイナー）。

### ③ ぜったいひつようなもの（dealbreaker・セグメント別）
- **共通**: 公開 SEO/OGP/canonical/JSON-LD/SSR 統合（全員）／WordPress 移行（WXR＋301）（4人）。
- **個人ブロガー**: パスワード忘れフォーム＋2FA、iPhone で投稿〜公開が快適。
- **小売**: EC（商品/カート/**ローカル決済** Paystack/Flutterwave）＋問い合わせ/注文フォーム。
- **代理店**: 公開**多言語**、きめ細かい権限＋2FA、Superadmin の管制塔化。
- **報道**: **Google News/Discover 用 NewsArticle JSON-LD＋安定SSR**、Writer/Editor/Publisher のロール分離＋エンバーゴ、予約発火の信頼性（TZ）、GDPR/EAA(a11y)対応。
- **開発者**: **relation 型のスキーマUI実用化**、machine-to-machine の**API トークン＋細粒度スコープ**、拡張点（custom field type/hook）。
- **デザイナー**: 編集の **WYSIWYG/ライブプレビュー**、公開多言語。

### ④ 現状不便なもの（実作業の摩擦）
- **ブロックエディタが縦積みフォーム式・WYSIWYG 無し（shot 05）** — 「公開して初めて見た目が分かる」（全員が言及、デザイナーは"2010年代"と酷評）。
- **設定に生 JSON 露出（shot 14）** — 「見た瞬間うっ無理」「クライアント編集者に絶対渡せない」（非技術系全員）。
- **公開トップで一瞬「Loading/0件」（shot 18/22）** — 細い回線では「壊れてる/遅い」に見える（小売）。
- **未配線デッドコードで詰まる** — widget の header/footer 領域、フィールド region、relation 型 UI（「設定できるのに効かない」は最悪の罠）。
- **公開トップに "60+ MCP ツール" を読者向け統計表示（shot 18）** — 報道読者にはノイズ＝プロダクトの軸足が開発者向けに透ける。
- **README/llms.txt が古い**（545 tests / 60+ tools / port 8080）— 「docs の正確性は DX の信頼そのもの」（開発者）。
- タグ追加が選択式で新規が打ちづらい／画像トリミング不可／メディア検索不可（ブロガー）。

### ⑤ こうすればニーズが増える（セグメント獲得の打ち手）
- **個人クリエイター層**: SEO/OGPデフォON → WP インポーター → 「写真UPでAIが下書き/SEO/altを生成」というクリエイター体験へ MCP を翻訳 → ジャンル別"映えテンプレ"。
- **新興国SME**: 「軽さ・安さ・速さ」を実測ベンチで前面化 → ローカル決済＋WhatsApp注文動線 → 現地通貨課金 → PWA/オフライン耐性。
- **代理店**: 「N個のWPを1基盤へ畳む」＋Superadmin管制塔（横断稼働/課金/雛形クローンで"10分で新規クライアント"）＋ホワイトラベル＋公開多言語。
- **報道**: SEO報道グレード化（news sitemap/RSS/JSON-LD）→ WP移行ツール(301)→ 編集ワークフロー製品化 → MCP で見出しA/B・要約・タグ自動化。
- **開発者**: MCP を"動く"状態に＋`create-nene-app` 60秒quickstart＋relation実用化＋API トークン＋拡張点公開。
- **若手デザイナー**: WYSIWYG＋ClaudeDesign実デモ＋a11yを可視機能化＋学生無料＋宣言的テーマのリミックス共有。

### ⑥ こうすれば人気が出る（口コミ/バズの条件）
- **AI 一点突破**: 「Claudeに喋るだけでテーマ/ページが変わる」15秒クリップ（TikTok/Reels/X）。**Strapi/Directus/Webflow/Wix の誰も持たない角度**（開発者・デザイナー・ブロガー一致）。
- **"The first MCP-native headless CMS" として Show HN**（開発者）— 比較表で弱点も正直に出すと逆に信頼。
- **OGPが映える＝勝手に宣伝される設計**（SEO修正が前提）。今は OGP 欠如で拡散の起爆剤が死んでいる。
- **WP からの移行成功事例1本**（中規模媒体/代理店）— 横並びで真似る業界に最も効く。
- **セキュリティ/低保守を合言葉に**（「プラグイン脆弱性ゼロ・更新地獄なし」）— WP疲れコミュニティに刺さる。
- **学生/クリエイター・アンバサダー＋無料テーマ配布**（Figma/Notion が学生無料で広がった構図）。
- **ダークモード標準＋editorialテーマのギャラリー映え**（Gen-Z）。

---

## 4. ペルソナ別 総評（要約）

1. **田中 美咲（ブロガー）** 🔶条件付き: 「可愛い・写真最適化・内蔵解析には本気で惹かれたが、OGPも構造化データも出ない＝拡散も検索流入も死ぬのが致命的。中身は未来っぽいのに集客の生命線だけが穴」。反転条件=SEO/OGP＋WP移行＋PWリセット&モバイル投稿。
2. **James Okafor（小売）** 🔴見送り: 「画像の軽さと解析は本物で回線事情に唯一寄り添う。だが商品が売れず（EC無）、シェアしても見栄えせず（OGP無）、WPから連れて行けない。"速い読み物サイト"であって"俺の店"じゃない」。反転条件=ローカル決済EC＋SSR/OGP＋WP/Woo移行。
3. **Sofía Hernández（代理店）** 🔶条件付き: 「思想と中身はWP疲れした代理店の理想に最も近い。が公開SEO/SSRと多言語が未完では納品できない（SEOは客が金を払う最大の理由）」。反転条件=公開SEO＋公開多言語＋WP移行＋細粒度権限。
4. **Liam O'Sullivan（報道）** 🔶条件付き(実質見送り): 「素地は美しく画像/Webhook/解析/ブロックはWP超え。だが読者に届くページがSPAでOGPもcanonicalもJSON-LDも無く、SSRは別URLに孤立＝商売の心臓を撃ち抜く欠陥」。反転条件=報道グレードSEO＋WP移行(301)＋編集ロール分離。
5. **陳 偉（開発者）** 🔶条件付き: 「思想と型とMCPはmodern headlessでも頭一つ抜けて魅力的。だがrelation未配線・APIトークン不明・拡張点ゼロで本番backendには穴が多い。AI-nativeの旗だけが本物」。反転条件=relation実用化＋APIトークン＋MCP動くデモ。
6. **Aisha Rahman（デザイナー）** 🔶条件付き: 「型付きブロック・チャート・a11y・AI構想・テーマの美しさは理想に最も近いのに、"見ながら作れない(WYSIWYG無)"と"SNS/Googleに出ない(SEO/OGP無)"の2点で恋する前に現実に戻される」。反転条件=WYSIWYG＋SEO/OGP＋ClaudeDesign実働。

---

## 5. 優先度付き提言（横断統合ロードマップ）

| 優先 | 施策 | 解決するペルソナ | 既存 issue との関係 | 難易度 |
| --- | --- | --- | --- | --- |
| **P0** | **公開サイトの SEO/SSR 統合**: SPA本番ルートに SSR/プリレンダを統合（`/view` ツイン廃止 or 統合）、**OGP・Twitter Card・canonical・JSON-LD(Article/NewsArticle)・sitemap/RSS**、per-entity SEOメタをSSRへ反映 | **6/6 全員** | 新規エピック化が必要（#491 S3e の JSON-LD は custom page 限定で部分的にしか重ならない） | 大 |
| **P0** | **編集のライブプレビュー/WYSIWYG**（ブロック盤面に `BlocksRenderer` 組込＋テーマ iframe プレビュー） | 5/6 | handoff §4.2「任意・体感大」として既知。`BlocksRenderer` は公開専用 import を編集側にも | 中 |
| **P1** | **WordPress 移行（WXR インポート＋メディア取込＋301 リダイレクトマップ）** | 4/6 | docs上は「後回し」（hard non-goal ではない） | 中 |
| **P1** | **公開サイト i18n**（detail/アーカイブの多言語化） | 代理店/報道/デザイナー | roadmap「Next Phase Candidates」に既出 | 中 |
| **P1** | **設定の生JSON露出を構造化UIへ**（Theme customizations/Layout/Header） | 非技術系全員 | — | 小〜中 |
| **P1** | **relation 型のスキーマUI実用化**（targetEntityType/cardinality 入力をフィールド定義に配線） | 開発者/代理店/報道 | code/handoff 既知（backendは実装済み・UI未配線） | 小〜中 |
| **P1** | **権限の細分化＋2FA＋PWリセットフォーム＋API トークン(scoped)** | 代理店/報道/開発者/ブロガー | API トークンは #491 S3e「scoped creds」と一部重なる | 中 |
| **P2** | **デッドコード解消**（widget header/footer 配線 or 撤去、field region） | 全員（罠回避） | handoff §4.2 既知 | 小 |
| **P2** | **MCP/ClaudeDesign を"動くデモ"化**（差別化の核を運用 UX へ） | 全員（将来性） | #373 / MCP connector handoff（常時接続未確立） | 大 |
| **P2** | **コメント強化**（スレッド/スパムキュー/管理返信）、**メニュー DnD/ネスト**、**画像 crop/検索** | 報道/小売/ブロガー | — | 中 |
| **P2** | **README/llms.txt の更新**（古い 545/60+/8080） | 開発者（信頼） | 既知ドリフト | 小 |
| **scope外** | **EC/決済・フォーム** | 小売 | 意図的 Non-Goal（要・経営判断） | 大 |

### 最重要メッセージ
**P0 の「公開 SEO/SSR」一点に集中投資すれば、6人中5人の最大の dealbreaker が同時に外れる。** これは本レポートの最大の発見であり、投資対効果が突出している。AI-native（MCP/ClaudeDesign）は唯一無二の差別化だが、"動くデモ"になるまでは採用理由にならない＝**P0で土台を固め、P2でAIを花にする**順序が合理的。

---

## 6. 既存方針との整合メモ
- **意図的 Non-Goal を侵さない提言である**こと: 上記は「任意JS解禁」「Elementor化」「DB直アクセス」を求めていない。SEO/SSR・WYSIWYG・移行・権限はいずれも Non-Goal と非抵触。
- **EC は明示的にコア外**。小売ペルソナの要望は「別プロダクト/連携」で受けるのが方針整合的（roadmap の "AI Consumer Chat は別リポジトリ" と同じ発想）。
- **AI Consumer Chat / Consumer i18n** は既に roadmap 候補。本レポートはそれらの優先度を「市場ニーズが高い」と裏付ける。

---

## 付録: 取得スクリーンショット一覧
01 login / 02 dashboard / 03 content-types / 04 records-list / 05 record-editor(blocks) / 06 field-defs / 07 media / 08 appearance-layout / 09 menus / 10 appearance-theme / 11 appearance-customize / 12 comments / 13 users / 14 settings / 15 notifications / 16 webhooks / 17 superadmin-orgs / 18 public-home / 19 public-browse / 20 public-detail(blocks) / 21 mobile-admin / 22 mobile-public
（`scratchpad/shots/` 一時保管・desktop 1440×900 / mobile 390×844）

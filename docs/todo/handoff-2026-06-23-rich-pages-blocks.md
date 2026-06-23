# 引継ぎ — リッチページ／ブロックシステム ＋ 型チェック是正

最終更新: 2026-06-23 / 関連 Issue: #486(完了) #489(完了) #491(進行中) #311(進行中) / ブランチ: なし（全て main にマージ済み・open PR 0）

このセッションで「型付き投稿ブロック（#486）」「フロント型チェックの実稼働化（#489）」「リッチページ化 EPIC（#491 WS1–WS3）／カスタムページ契約（#311 S3a–S3d）」を実装・マージした。**実用ラインは達成**しており、残るは **#311 S3e（ClaudeDesign 連携＋スコープ資格情報＋JSON-LD）** と、いくつかの後始末。中断時点は「S3d までマージ完了、S3e は未着手（別レイヤの大物・要設計）」。

---

## 1. いまどこまで来たか（状態）

### 完了・マージ済み
- ✅ **#486 型付き投稿ブロック**（完了・クローズ）: 本文ブロック5種 `text / callout / hero / gallery / chart` ＋ ホーム hero。第一者描画（任意JS無し・C2）／サーバ検証（信頼境界）／sr-only SEO 投影（C4）／URL allowlist（オープンリダイレクト遮断）。
- ✅ **#489 型チェック是正**（完了・クローズ）: `npm run type-check`/`build` が root tsconfig（files:[]＋references・`-b` 無し）で **src を1ファイルも検査していなかった**のを `tsc -p tsconfig.app.json --noEmit` に修正。隠れていた tsc エラー **1197 → 0**。実バグ（HomePage 権限常時 false 等）も是正。以後 CI が型を実検査。
- ✅ **#491 WS1**（マージ #492）: コンテンツタイプ作成「スターター」（blank / article / **rich_page**=title+blocks / **custom_page**=title+bundle+custom layout）。
- ✅ **#491 WS2**（マージ #493/#494/#495）: レイアウトブロック `group`（囲み・tone）/ `columns`（2–4 段・レスポンシブ）/ `spacer` / `divider`。ネスト基盤（深さ2・leaf-only 子）。WS2-S2c で多視点レビューの確定3件も修正（再帰検証・schema leaf-only・上限強制）。
- ✅ **#311 / #491 WS3**（マージ #496–#499）:
  - **S3a**（#496）dual-SEO: bundle を `{ html, seoText }` エンベロープ化。`seoText`(markdown) を SSR＋consumer(sr-only) でクローラブル描画。`seoText` 必須をサーバ強制。
  - **S3c**（#497）`custom` レイアウトを full-bleed・chrome-less な page ホストに（従来 inert）。
  - **S3b**（#498）`text_fields.value` を TEXT→**LONGTEXT**（実寸バンドル可）＋サイズ上限引き上げ。
  - **S3d**（#499）「Custom page」スターターで作成ターンキー化（title+content(bundle)+default_layout=custom）。

### 未着手
- ⬜ **#311 / #491 WS3-S3e**: ClaudeDesign パイプライン＋スコープ資格情報＋JSON-LD（**次にやるべきこと・§4**）。

## 2. すぐ確認できる runbook

```bash
# 全ゲート
composer check                      # PHPUnit + PHPStan lvl8 + CS-Fixer + OpenAPI + MCP（現状 845 tests 緑）
npm run check --prefix frontend     # type-check(実検査) + lint + prettier + test + themes + knip + storybook（302 tests 緑）

# bundle 検証の実機スモーク（シード: admin@nene-records.local / nene1234・writes は X-Requested-With 必須）
#  - bundle 値は {"html","seoText"} エンベロープ。seoText 空 → 422。~200KB も 201（LONGTEXT）。
#  - field_def data_type=bundle の値は /api/v1/text-fields に POST（text-backed 共用）。
```

「Custom page」を作る手順（管理画面）: コンテンツタイプ作成フォーム → スターター=「Custom page」→ 作成（title+content(bundle) 自動付与・default_layout=custom 自動設定）→ 記事編集で content(bundle) に HTML＋必須 SEO テキストを書く → 公開すると full-bleed・サンドボックス＋クローラブルに描画。

## 3. アーキテクチャ / 主要ファイル

| 役割 | 場所 |
| --- | --- |
| ブロック doc モデル（pure TS・parse/serialize/validate） | `frontend/src/shared/lib/blocks-document.ts` |
| ブロック公開描画（第一者・9種ディスパッチ・group/columns 再帰） | `frontend/src/shared/ui/blocks/BlocksRenderer.tsx` |
| ブロック編集（パレット＋盤面＋インスペクタ・上限強制） | `frontend/src/features/edit-entity-text-fields/ui/BlocksFieldEditor.tsx` ＋ `BlockInspector.tsx`（GroupChildrenField がコンテナ子を再帰編集） |
| ブロック サーバ検証（信頼境界・深さ2・総ノード上限） | `src/BlocksField/BlocksDocumentValidator.php` ＋ `BlockTypes.php` ＋ `docs/blocks/blocks.schema.json`（`leafBlock` $def でネスト禁止を表現） |
| bundle モデル／検証 | `frontend/src/shared/lib/bundle-document.ts` ／ `src/BundleField/BundleDocumentValidator.php`（seoText 必須・サイズ上限） |
| bundle 公開（chrome-less iframe＋sr-only seoText） | `frontend/src/features/public-view-entity-record/ui/PublicRecordFieldList.tsx` ＋ `shared/ui/html/SandboxedBundle.tsx`（`sandbox="allow-scripts"`・**no `allow-same-origin`**） |
| bundle SSR（seoText を markdown 描画＝クローラブル） | `templates/public/record-detail.php`（`$field->dataType==='bundle'` 分岐）＋ `src/PublicRecord/RenderPublicRecordViewHandler.php`（`renderBundleSeo`） |
| custom レイアウト host | `frontend/src/pages/consumer/PublicRecordDetailPage.tsx`（`variant==='custom'`）＋ `.custom-page`（public-site.css） |
| コンテンツタイプ スターター | `frontend/src/features/manage-entity-types/hooks/use-create-entity-type-form.ts`（`ENTITY_TYPE_STARTERS`）＋ `use-manage-entity-types-page.ts`（`STARTER_FIELDS`・作成後 default_layout 更新） |

**ブロック型を1つ足す手順**: `BlockTypes.php` ＋ `BlocksDocumentValidator`（必要なら `match` ＋ 専用 validate）＋ `docs/blocks/blocks.schema.json` ＋ frontend `blocks-document.ts`（型/coerce/serialize/validate）＋ `block-catalog.ts` ＋ `BlockInspector` 分岐 ＋ `BlocksRenderer` 分岐 ＋ i18n(en/ja)。コンテナ型なら children は leaf-only（`CONTAINER_TYPES`／`isLeafBlock`）で深さ2を維持。

## 4. 残タスク / 次にやるべきこと

### 4.1 最優先: #311 / #491 WS3-S3e（ClaudeDesign 連携＋scoped creds＋JSON-LD）
AI 著作側の大物。独立設計が要る。要素:
1. ⬜ **スコープ付き資格情報**: ClaudeDesign は **OpenAPI/MCP 境界経由のみ**で page を編集（admin 丸ごとは渡さない）。bundle/seoText を書く専用クレデンシャル＋認可（現状は汎用 `createTextField` MCP で無スコープ）。
2. ⬜ **ClaudeDesign パイプライン**: bundle 生成 → bundle フィールドへ書込の一連。ガイド（`docs/theming/claudedesign-mcp-guide.md` は theme 専用なので page 版が要る）。
3. ⬜ **JSON-LD / 構造化データ**: custom page の SSR に出力（現状ゼロ。`grep json-ld` で無し）。
4. ⬜ （任意）**bundle_assets**: media 参照（#299 ストレージ流用）。現状は完全自己完結インライン前提。

### 4.2 後始末（このセッションで判明・小〜中）
- ⬜ **フィールド `region`（sidebar/aside）はレコードでデッドコード**: `PublicRecordRegionGrid` は export されるがどのページからも import されていない（`regionForLayout`/`layoutRegions` もそこだけ）。**配線するか撤去するか**を決める（field-def の region セレクタも整理）。※前回ユーザーに説明した region 挙動は実際には未配線、の訂正。
- ⬜ **docs/todo/current.md のドリフト**: 「Custom Page 3A/3B done」等が過大表記。`verify-state-via-git` 方針で git/Issue を真とする。current.md を実態（#311 S3a–S3d 済 / S3e 残）に更新。
- ⬜ **bundle の low 項目**（レビューで意図的に見送り）: `divider` data のサーバ厳格検証（schema additionalProperties:false が未強制）／全空 `columns` が空グリッドを描画／`SandboxedBundle` の HEIGHT_REPORTER が author CSP で詰まる端／公開ページに **サーバ CSP backstop 無し**（iframe の sandbox 属性が唯一の封じ込め＝将来 frame-src/sandbox CSP を検討）。
- ⬜ **エディタのライブプレビュー（任意・体感大）**: ブロック編集は現状フォーム式（WYSIWYG 実描画なし）。`BlocksRenderer` を編集画面に組み込むスライスで Gutenberg 風プレビュー可。

### 4.3 既知の注意（型）
- `exactOptionalPropertyTypes` ＋ `noUncheckedIndexedAccess` を**維持**（owner 判断）。新 mapper は codegen が `?: T|null` を吐くため `?? null`／条件スプレッドのノイズが再発する。**`!`(非null assertion) は lint 禁止**＝ガード/`??`/`?: T|undefined` で対処。詳細はメモリ `frontend-typecheck-strict`。

## 5. ハマりどころ（既知）

- **`npm run type-check` は実検査になった**（#489）。ただし root の `tsc --noEmit` は今も no-op。型確認は `tsc -p tsconfig.app.json --noEmit`（=スクリプト）で。
- **マイグレーション版番号の date-skew**: `composer migrations:new` は今日日付（例 20260623）を使うが、リポジトリは未来日付の依存（organizations 20260628 / home_hero 20260706）を持つ → fresh DB で依存より前にソートされ得る。**現行最大 > の版番号を手で付ける**（本セッションの 20260707 を踏襲）。実 MySQL で `migrations:migrate` 検証。
- **bundle は text_fields 共用**（専用テーブル無し）。値は `{html,seoText}` エンベロープ。旧・生 HTML の bundle は read 時に後方互換（`{html: raw, seoText:''}`）だが、**update 時は seoText 必須で 422**（既存生 bundle があれば要 seoText 補完）。
- **bundle サンドボックスの不変条件**: `sandbox="allow-scripts"` に **`allow-same-origin` を絶対に足さない**（足すと隔離が崩れる）。テスト `SandboxedBundle.test.tsx` が固定。セッショントークンは **HttpOnly Cookie**（localStorage の JWT ではない＝メモリの旧記述は訂正済み）。
- **design-export/ は untracked 作業ディレクトリ**（`.git/info/exclude`）。`git add` しない。ClaudeDesign の zip/handoff はここ。

## 6. 関連 PR / Issue

| # | 内容 | 状態 |
| --- | --- | --- |
| #486 | 型付き投稿ブロック EPIC | マージ・**クローズ** |
| #489 | フロント型チェック是正（1197→0） | マージ・**クローズ** |
| #491 | リッチページ化 EPIC（WS1–WS3） | open（WS1/WS2/WS3 S3a–S3d 済・S3e 残） |
| #311 | Custom Page 厳格契約（トラッカー） | open（S3a–S3d 済・S3e 残） |
| #492–#499 | WS1〜WS3-S3d の各スライス | 全てマージ済み |

> ブロック設計の全体像は memory `block-system-epic`、型チェック方針は memory `frontend-typecheck-strict`、カスタムページ方針は memory `custom-pages-direction`（JWT 記述は 2026-06-23 訂正済み）。

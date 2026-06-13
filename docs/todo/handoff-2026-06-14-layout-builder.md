# Handoff: 外観 › レイアウト（レイアウトビルダー）実装中 (2026-06-14)

ClaudeDesign 仕様（`appearance-layout-handoff` / 配布名 `nene-records_008`）に基づく
**レイアウトビルダー**を多スライスで実装中。本書は途中再開のための引き継ぎ。

- エピック: **#352**（`epic(appearance): レイアウトビルダー「外観 › レイアウト」`）
- 仕様の正本（挙動・見た目）: `design-export/appearance-layout-handoff/`（ローカル非追跡・/tmp から退避済み）
  - `prototype-standalone.html`（まず触る）, `IMPLEMENTATION.md`, `DATA-MODEL.md`, `prototype/*.jsx`, `screenshots/`
  - **プロトタイプの挙動が正**。本番は既存コンポーネント/トークンで再実装する方針。

---

## 確定済みの方針（ユーザー承認）

1. **IA 改称**: 旧 `/admin/widgets`・`/admin/navigation` を廃し **「外観 › レイアウト」** に統合。
   ルート `/admin/appearance/layout`（レイアウトタブ）/ `/admin/appearance/menus`（メニュータブ）。旧ルートはリダイレクト。
2. **配置は region に一本化**（先行エピック #347 で実施済み）: 置き場所＝`header`/`sidebar`/`aside`/`footer`、`main` は本文。
   メニューは「名前付きリンク集」で location を持たず、`menu` ウィジェット(`settings.menuId`)経由で表示。
3. **レイアウト構成 cfg**（`{columns,mainPos,swap}`）の永続: **v1 はサイト設定にグローバル1つ。将来「全体/タイプ別」スコープを足せる設計**。
   ※現状 cfg は **localStorage 永続のみ**（プロトタイプ準拠）。サイト設定への永続化＋公開反映は後続スライス（下記6）。

---

## 進捗（スライス）

| # | 内容 | 状態 | PR |
| --- | --- | --- | --- |
| 1 | 新IA＋タブ式シェル＋メニュー管理(master–detail) | ✅ 完了 | #353 |
| 2 | レイアウト構成バー＋cfg反映（localStorage）＋非表示警告 | ✅ 完了 | #354 |
| 3 | **DnD ビルダー**（パレット／ドラッグ配置・領域間移動・並べ替え・ドロップ位置インジケータ／インスペクタ） | ⬜ 次 | — |
| 4 | プレビューモード（ライブプレビュー）＋フォームモード | ⬜ | — |
| 5 | 仕上げ（オンボーディングツアー／ヘルプ関係図／トースト／レスポンシブ＝アイコンレール・モバイルドロワー／2テーマ） | ⬜ | — |
| 6 | cfg のサイト設定永続化＋**公開側の段組み反映**（既存 per-entity `layout` との整理含む） | ⬜ | — |
| 7 | 後片付け: `navigation_items.location` / `menus.location` 撤去、`NavLocations`/`MenuLocations` 整理 | ⬜ | — |

> 各スライスは `composer check` ＋ `npm run check` 全グリーンで PR→squash マージ。`main` は常に動作する状態を維持。

---

## 実装の現在地（ファイル）

- ルート: `frontend/src/app/router.tsx`（`appearance/:tab` ＋ 旧ルートの `<Navigate>` リダイレクト）
- シェル: `frontend/src/pages/appearance/AppearanceLayoutPage.tsx`（パンくず・タブ・件数バッジ。layout→`ManageWidgetsView`、menus→`ManageMenusView`）
- メニュー管理: `frontend/src/features/manage-menus/`（hook + `ManageMenusView`、master–detail、項目 上下並べ替え、配置コールアウト）
- レイアウトタブ（現状の領域ボード）: `frontend/src/features/manage-widgets/`
  - `ManageWidgetsView`（追加フォーム＋構成バー＋警告＋ボード）
  - `ui/WidgetRegionBoard.tsx`（header / bodyColumns(cfg) / footer。`main` は本文プレースホルダ）
  - `ui/LayoutConfigBar.tsx`（columns/mainPos/swap）
  - `hooks/use-manage-widgets-page.ts`（widget CRUD・region・menu選択 等）
- cfg: `frontend/src/shared/lib/layout-config.ts`（`LayoutConfig`・`bodyColumns`・`activeSideRegions`・localStorage）
- region 型: `frontend/src/shared/lib/resolve-layout.ts`（`WidgetRegion = header|sidebar|aside|footer`、`WIDGET_REGIONS`）
- データ層（既存・流用）: `entities/menu`・`entities/widget`・`entities/navigation-item`（いずれも CRUD＋公開クエリ済）
- 公開描画: `pages/consumer/PublicLayout.tsx`（header/footer は region ウィジェット描画）、`features/render-widgets/`（`SiteWidgets`・各 widget・`MenuWidget` は menuId 解決）

## バックエンド（実装済み・関連）

- `menus` テーブル＋ `navigation_items.menu_id`（#348）、Menu CRUD（`/api/v1/menus`・`/api/v1/public/menus`）
- widget region は `WidgetRegions`（header/sidebar/aside/footer）で検証（#351）
- nav item Create/Update は `menu_id` を受け付ける（更新は未指定なら保持・#349）
- **cfg 用のバックエンドは未実装**（スライス6で `layout_config` をサイト設定へ。`setting_defs` は org スコープ＝新規org シードに注意）

---

## 残スライスの要点（仕様 §参照）

- **スライス3 DnD**（IMPLEMENTATION §2A,§3）: 左パレット(ドラッグ可能チップ)→領域へドロップで追加、配置済みカードのドラッグで領域間移動＋領域内並べ替え、ドロップ位置インジケータ線、右インスペクタ（見出し/設定/領域編集）。`prototype/board.jsx` が一次ソース。
- **スライス4 プレビュー/フォーム**（§2B,§2C）: フォームモード（モーダル追加）、プレビューモード（`SitePreview` の縮小公開モック・トップ/レコード詳細切替・選択ハイライト）。`prototype/pieces.jsx` の `SitePreview`。
- **スライス5 仕上げ**（§5,§6,§7）: ツアー(localStorage `nene_layout_tour_seen`)・ヘルプ関係図・トースト・レスポンシブ(>1100/821–1100 レール/≤820 ドロワー)・テーマ（Default Light / Console Dark）。
- **スライス6 cfg backend＋公開反映**: `layout_config` をサイト設定 (`setting_defs`/`setting_values`) に追加し、公開レコードページの段組みへ反映。**既存 `entity.layout`・`entityType.defaultLayout` との重複整理**が論点（将来「タイプ別」スコープへ拡張可能に）。
- **スライス7 cleanup**: `location` 列・`NavLocations`・`MenuLocations` の撤去（マイグレーション＋参照削除）。

## 落とし穴 / 規約

- ESLint: **className に Tailwind 任意値 `[...]` 禁止**（shared/ui/theme 以外）→ 定数化 or インライン style。
  arrow shorthand の void 返却禁止（`onClick={() => { fn() }}`）。`no-unnecessary-condition` 等も厳しめ。
- i18n: `en.ts` が真実源、`ja.ts` は `Partial`。新キーは両方に追加。`npm run check` の prettier/knip/storybook まで通すこと。
- lint-staged が commit 時に eslint --fix + prettier --write を走らせる。
- マイグレーション生成は `composer migrations:new -- Name`（手動作成禁止）。適用は docker: `docker compose exec app vendor/bin/phinx migrate`。
- ブランチ `type/issue-番号-要約`、Conventional Commits（説明/本文は日本語、`(#issue)` 付与）、PR は squash。

## 関連エピック/履歴

- #324 ウィジェット（基盤〜カレンダー・完了）/ #347 名前付きメニュー（#348/#349/#351）/ #344 ドキュメント最新化。
- `docs/todo/current.md`・`docs/roadmap.md` は M15 まで最新化済み（レイアウトビルダー完了時に追記すること）。

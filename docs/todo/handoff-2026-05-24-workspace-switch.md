# Handoff: Workspace Switch and Project Kickoff (2026-05-24)

この文書は、**NENE2 ワークスペースから NeNe Records をメイン Cursor ワークスペースに切り替える**ための引き継ぎである。  
構想の経緯・ゴール・哲学の durable 版は [`docs/explanation/product-vision.md`](../explanation/product-vision.md) を正本とする。

---

## 1. ワークスペース切替

### 推奨設定

| 項目 | 値 |
| --- | --- |
| **メイン WS** | `/home/xi/docker/nene-records` |
| **リポジトリ** | https://github.com/hideyukiMORI/nene-records |
| **NENE2 を WS に追加** | 任意（未リリース機能・本体 PR 並行時のみ） |
| **NENE2-FT** | 通常は WS 外。パターン参照時だけ |

### NENE2 は composer だけで足りるか

**通常は足りる。** `composer require hideyukimori/nene2` で `vendor/hideyukimori/nene2/` にコードと docs が入る。  
実装・テスト・MCP 検証は vendor 参照で可能。

**NENE2 を sibling で WS に足すケース:**

- NENE2 未リリース HEAD を path repository で試す
- フレームワーク本体に並行 PR を出す
- `src/` 定義ジャンプを IDE で横断したい

```json
"repositories": [{ "type": "path", "url": "../NENE2" }],
"require": { "hideyukimori/nene2": "@dev" }
```

### AI / 人間が最初に読むファイル

1. [`AGENTS.md`](../../AGENTS.md)
2. [`docs/explanation/product-vision.md`](../explanation/product-vision.md) — **なぜ作るか**
3. [`docs/inheritance-from-nene2.md`](../inheritance-from-nene2.md) — **NENE2 継承境界**
4. [`docs/todo/current.md`](./current.md) — **今何をするか**
5. [`docs/roadmap.md`](../roadmap.md) — **フェーズ全体**

---

## 2. 構想の経緯（2026-05-24 対話サマリー）

### 出発点

hide が NENE2 で「有用でそこそこな規模のもの」を作れないかと検討。  
WordPress の `wp_posts` + `wp_postmeta` に似た **汎用エンティティ + 型付きデータ** モデルを提案:

- `entities`（旧 posts 相当）
- `data`（フィールド定義 + data_type）
- `int_data`, `text_data`, `enum_data`, …（型別 value テーブル）
- 全表共通の soft delete（`is_deleted`, `deleted_at`）

フロントから仮想テーブルを CRUD できれば、**DB っぽいものを自由に管理できるのでは** という発想。

### WordPress との距離

WordPress は似た柔軟性を実証したが、`postmeta` の string 化・フック地獄・テーマ/プラグイン密結合で **カオスになりやすい**。

NeNe Records は:

- **API（NENE2）** = 認証・ファイル・CRUD・契約
- **管理フロント** = スキーマ定義 UI + 主なビジネスロジック
- **Consumer View** = 公開表現（差し替え可能）

→ **API もフロントも差し替え可能** な責務分離。

### CMS を超える可能性

CRUD + スキーマレジストリ + 認可 + クエリ + リレーションまで行けば、CMS だけでなく **軽量アプリ基盤** になりうる。  
Level 3（ワークフロー・Webhook・マルチテナント等）は別プロダクト域 — Phase ごとに [`docs/roadmap.md`](../roadmap.md) 参照。

### MCP 構想

OpenAPI ベース MCP を充実させれば:

- 「5/24 のアクセス集計」
- 「あの記事のタイトル変更」
- 「php タグの記事一覧」

など **自然言語 → MCP ツール → HTTP API → DB** で実現可能。  
NENE2 の Note/Tag CRUD + MCP write パターンが原型。

### 命名・リポジトリ

- プロダクト名: **NeNe Records**（`posts` baggage を避ける）
- リポジトリ: https://github.com/hideyukiMORI/nene-records
- NENE2 **本体には入れない**（consumer project として独立）

---

## 3. ゴール

### 短期（Phase 0–1）

- [x] ガバナンス継承（Issue #2, ADR 0001, Cursor rules）
- [ ] MVP vertical slice（Issue #1）  
  `entity_types` + `entities` + `text_fields` CRUD + OpenAPI + SQLite tests + `composer.json`

### 中期

- 型追加（int, enum, image, file）
- スキーマレジストリ API（`field_defs`）
- タグ・リレーション・フィルタクエリ
- 管理フロント React

### 長期

- MCP セマンティックツール + アクセス集計
- Consumer View パターン
- 必要なら read model / キャッシュ最適化

---

## 4. 哲学（要約）

詳細は [`product-vision.md`](../explanation/product-vision.md)。

| 原則 | 意味 |
| --- | --- |
| API first | OpenAPI が契約の中心 |
| Typed, not meta | WP 型 `postmeta` 地獄を避ける |
| 責務分離 | API / Admin / Consumer / MCP を独立 |
| AI-native | MCP は DB 直結禁止、HTTP 契約のみ |
| NENE2 上に載せる | フレームワーク再発明しない |
| Issue 駆動 | NENE2 と同じ厳格運用を継承 |

---

## 5. NENE2 からの文脈

### NENE2 が既に提供するもの

- PSR-7/15/17 HTTP runtime、明示ルーティング、ミドルウェア
- OpenAPI + Swagger UI + contract tests
- RFC 9457 Problem Details
- Bearer JWT / API key / CompositeAuth
- PDO + Phinx migrations + repository パターン
- Local MCP server + `docs/mcp/tools.json`
- Note/Tag CRUD 参照実装（Phase 1 のテンプレート）

### NeNe Records が追加するもの

- 汎用エンティティモデルと型付きフィールド永続化
- スキーマレジストリ（API 側検証）
- 管理フロント・Consumer View（将来）
- プロダクト固有 MCP ツール（entity CRUD, analytics 等）
- Problem Details base: `https://nene-records.dev/problems/`

### 参照実装の場所

| 用途 | 参照先 |
| --- | --- |
| CRUD + UseCase パターン | NENE2 `src/Example/Note/`, `Tag/` |
| Consumer project 構成 | NENE2-FT（例: `noteslog/`） |
| フレームワーク docs | `vendor/hideyukimori/nene2/docs/` |
| ガバナンス継承 | [`docs/inheritance-from-nene2.md`](../inheritance-from-nene2.md) |

---

## 6. 設計上の注意（対話で合意した論点）

### スキーマはフロントだけにしない

`field_defs` を API で永続化し、write 時に API が検証する。

### 型テーブルはハイブリッド

全部 typed テーブル分割しすぎない。コア型は typed、拡張 escape hatch に JSON カラムも検討。

### パフォーマンス

API は WP プラグイン地獄より速くなりやすいが、EAV JOIN / N+1 を放置すると遅くなる。  
repository で eager load、必要なら read model。

### 命名

テーブル・API では `entities` / `entity_types` / `field_defs` を優先。`posts` は使わない。

---

## 7. 完了済み作業

| 日付 | 内容 |
| --- | --- |
| 2026-05-24 | リポジトリ bootstrap、Issue #1 MVP 計画 |
| 2026-05-24 | Issue #2 ガバナンス継承 → PR #3 merge |
| 2026-05-24 | 本 handoff + product-vision 作成（Issue #4） |

---

## 8. 次のアクション

1. **Cursor WS を `/home/xi/docker/nene-records` に切替**
2. **Issue #1** — `feat/1-entity-types-crud` ブランチで runtime スキャフォールド
3. Phase 1 着手前に entity ER 図を `docs/` に固定（ADR 0002 候補）
4. NENE2 は composer require のみで開始。path repo は必要になってから

---

## 9. 関連リンク

- Product vision: [`docs/explanation/product-vision.md`](../explanation/product-vision.md)
- Roadmap: [`docs/roadmap.md`](../roadmap.md)
- Inheritance: [`docs/inheritance-from-nene2.md`](../inheritance-from-nene2.md)
- ADR 0001: [`docs/adr/0001-inherit-nene2-governance.md`](../adr/0001-inherit-nene2-governance.md)
- Issue #1: https://github.com/hideyukiMORI/nene-records/issues/1

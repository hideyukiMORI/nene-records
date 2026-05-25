# Naming Conventions

NeNe Records における命名規則の単一ソース。PHP・TypeScript 両面で **業界最高水準** の厳格さを適用する。

このドキュメントに記載されていない例外はすべて ADR で承認されなければならない。命名規則違反は `main` へのマージをブロックする。

---

## Document map

| Section | Covers |
| --- | --- |
| [Enforcement](#enforcement) | ツールと人によるゲート |
| [PHP — クラスロール別](#php--クラスロール別命名) | 全クラス種別の命名ルール |
| [PHP — メソッド](#php--メソッド命名) | メソッド・パラメータ・ローカル変数 |
| [PHP — プロパティと定数](#php--プロパティと定数) | フィールド・定数・enum |
| [PHP — ファイル・名前空間](#php--ファイルと名前空間) | ファイル名・名前空間パターン |
| [PHP — テスト](#php--テスト命名) | テストクラス・メソッド・ダブル |
| [TypeScript — ファイル](#typescript--ファイル命名) | ファイルシステム命名 |
| [TypeScript — 型・インターフェース](#typescript--型とインターフェース) | 型レベルの命名 |
| [TypeScript — 変数・関数](#typescript--変数と関数) | ランタイムシンボル命名 |
| [TypeScript — エンティティ層固有](#typescript--エンティティ層固有) | mappers・query-keys・query hooks |
| [TypeScript — コンポーネント・フック](#typescript--コンポーネントとフック) | React 固有ルール |
| [TypeScript — テスト](#typescript--テスト命名) | テストファイル・describe・it |
| [クロスカッティング](#クロスカッティングルール) | API フィールド・ルートパス |
| [違反チェックリスト](#違反チェックリスト) | PR セルフレビュー用 |

---

## Enforcement

| ゲート | ツール | タイミング |
| --- | --- | --- |
| PHP クラス・メソッド命名 | PHPStan (level 8) + PHP-CS-Fixer + PR review | merge 前必須 |
| PHP enum vs class | PHPStan custom rule (将来) / PR review | merge 前必須 |
| TS ファイル・シンボル命名 | TypeScript strict + ESLint + PR review | merge 前必須 |
| TS 境界違反 | `eslint-plugin-import` / `boundaries` | `npm run lint` で CI 失敗 |
| API フィールド名 | OpenAPI lint + contract test | `composer openapi` で CI 失敗 |

---

## PHP — クラスロール別命名

### 基本原則

- **すべてのシンボルは PascalCase**（クラス・インターフェース・enum・trait・namespace）
- ファイル名はクラス名と完全一致（`EntityType.php` → `class EntityType`）
- **1 ファイル = 1 クラス/インターフェース/enum/trait**
- 略語も PascalCase: `PdoEntityRepository`（`PDOEntityRepository` 不可）、`JsonResponseFactory`（`JSONResponseFactory` 不可）

### ドメインエンティティ

```
{Entity}.php
```

| 規則 | 例 |
| --- | --- |
| ドメイン集約名のみ、接尾辞なし | `EntityType`, `Entity`, `TextField`, `Tag` |
| `final readonly class` | — |
| コンストラクタで全フィールド | — |

### 入力 / 出力 DTO

```
{Verb}{Entity}Input.php
{Verb}{Entity}Output.php
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| 動詞 + エンティティ名 + `Input`/`Output` | `CreateEntityInput`, `GetEntityByIdOutput` | `EntityCreateDTO`, `CreateEntityRequest` |
| 動詞は英語 原型: `Create` `Get` `Update` `Delete` `List` `Attach` `Detach` | — | `Creating`, `Getter` |
| `final readonly class` | — | — |

### リストアイテム DTO

```
List{Entity}Item.php
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| **必ず** `List` 接頭辞 + エンティティ名 + `Item` 接尾辞 | `ListEntityItem`, `ListEntityTagItem`, `ListEntityRelationItem` | `EntityTagListItem`, `EntityRelationListItem` |
| `final readonly class` | — | — |

> **根拠:** `ListEntityTagItem` はクラス名を「`EntityTag` に対する List アイテム」と読める。`EntityTagListItem` は曖昧（`EntityTag` の中の `ListItem` か？）。

### 検索 / フィルタ基準 DTO

```
{Entity}ListCriteria.php
```

| 規則 | 例 |
| --- | --- |
| エンティティ名 + `ListCriteria` | `EntityListCriteria` |
| `final readonly class` | — |

### ユースケースインターフェース + 実装

```
{Verb}{Entity}UseCaseInterface.php
{Verb}{Entity}UseCase.php
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| インターフェースは末尾 `Interface` | `CreateEntityUseCaseInterface` | `ICreateEntityUseCase` |
| 実装はインターフェース名から `Interface` を除いたもの | `CreateEntityUseCase` | `CreateEntityUseCaseImpl` |
| `final readonly class` 実装 | — | — |
| 単一メソッド `execute(Input): Output` | — | — |

### リポジトリインターフェース + PDO 実装

```
{Entity}RepositoryInterface.php
Pdo{Entity}Repository.php
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| インターフェースは `{Entity}RepositoryInterface` | `EntityTypeRepositoryInterface` | `IEntityTypeRepository` |
| PDO 実装は `Pdo{Entity}Repository` | `PdoEntityTypeRepository` | `EntityTypeRepository`, `EntityTypeRepositoryPdo` |
| `final readonly class` 実装 | — | — |

### HTTP ハンドラー

```
{Verb}{Entity}Handler.php
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| 動詞 + エンティティ名 + `Handler` | `CreateEntityHandler`, `ListEntityTypesHandler` | `EntityController`, `CreateEntityAction` |
| 複数形 `List`: `List{Entity}sHandler` | `ListEntityTypesHandler` | `ListEntityTypeHandler` |
| `final readonly class`; メソッド名は `handle(ServerRequestInterface): ResponseInterface` | — | — |

### 例外クラス

```
{Entity}{Condition}Exception.php
{Entity}{Condition}ExceptionHandler.php
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| `Exception` 接尾辞 | `EntityNotFoundException`, `DuplicateEntitySlugException` | `EntityNotFound`, `EntityError` |
| HTTP マッパーは `ExceptionHandler` 接尾辞 | `EntityNotFoundExceptionHandler` | `EntityNotFoundMapper` |
| `final class extends \Exception` または `\RuntimeException` | — | — |

### ルート登録 / サービスプロバイダー

```
{Entity}RouteRegistrar.php
{Entity}ServiceProvider.php
```

| 規則 | 例 |
| --- | --- |
| `{Entity}RouteRegistrar` | `EntityTypeRouteRegistrar` |
| `{Entity}ServiceProvider` | `EntityTypeServiceProvider` |

### Enum

```
{ConceptName}.php   (PascalCase、接尾辞なし)
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| **固定文字列集合はすべて `enum` で表現** (`class` + 定数 不可) | `EntityStatus`, `Role`, `Capability`, `SettingRevisionAction` | `class EntityStatus { const DRAFT = ... }` |
| backed string enum: `enum {Name}: string` | `enum EntityStatus: string` | — |
| case 名は **PascalCase** | `case Draft`, `case Published`, `case Admin` | `case DRAFT`, `case draft`, `case ADMIN` |
| 純粋 enum（値なし）も case 名は PascalCase | `case ManageSchema`, `case EditContent` | `case MANAGE_SCHEMA` |

### 定数

固定文字列集合は enum を使う。その他の定数（マジックナンバー、設定値、文字列テンプレート）は定数クラス不可 — 関連するクラスまたは enum に `public const` として配置する。

---

## PHP — メソッド命名

### 基本ルール

- **camelCase**（先頭小文字）
- 動詞で始まる
- アクセサに `get` / `set` 接頭辞は **使わない**（readonly props で直接公開）

### リポジトリメソッド

| 操作 | メソッド名パターン | 例 |
| --- | --- | --- |
| ID で 1 件取得 | `findById(int): ?Entity` | `findById(42)` |
| 条件で 1 件取得 | `findBy{Condition}(...)` | `findBySlug(string, int)` |
| 複数取得 | `findAll(...)` または `findBy{Criteria}(...)` | `findAll(20, 0)` |
| 件数取得 | `count` または `countBy{Criteria}(...)` | `countByCriteria(...)` |
| 存在確認 | `existsBy{Condition}(...)` | `existsBySlug(...)` |
| 単一エンティティ保存 (insert) | `save(Entity): int` | — |
| 更新 | `update(Entity): void` | — |
| ソフトデリート | `softDelete(int): void` | — |
| 集約操作 | `{Verb}By{Condition}(...)` | `aggregateByDate(...)` |

### ユースケースメソッド

| 操作 | メソッド名 |
| --- | --- |
| 唯一の公開メソッド | `execute({Operation}Input): {Operation}Output` |
| プライベートヘルパー | camelCase、動詞先頭 (`normalizeSlug`, `buildEntries`) |

### ハンドラーメソッド

| 操作 | メソッド名 |
| --- | --- |
| 唯一の公開メソッド | `handle(ServerRequestInterface): ResponseInterface` |

### バリデーションヘルパー (enum の static メソッド)

| 操作 | メソッド名 |
| --- | --- |
| 値の配列を返す | `cases()` (組み込み) |
| 文字列からパース (失敗可能) | `tryFrom(string): ?self` (組み込み) |
| 文字列からパース (失敗時例外) | `from(string): self` (組み込み) |

> `isValid()` / `values()` などの手書きヘルパーは backed enum では不要。`tryFrom()` で代替。

### パラメータ・ローカル変数

- camelCase
- boolean: `is` / `has` / `can` 接頭辞（`$isDeleted`, `$hasSlug`）
- コレクション: 複数形（`$entityTypes`, `$items`, `$rows`）
- 生 HTTP データ: `$raw` 接頭辞（`$rawTypeId`, `$rawStatus`）
- 中間値: 意味のある名前（`$normalized`, `$existing`, `$slug`）
- ループ変数: コレクション名の単数形 or 意味あり（`$item`, `$row`, `$entity`）

---

## PHP — プロパティと定数

### インスタンスプロパティ

- **camelCase**
- `readonly` → コンストラクタで公開 (`public readonly`)、または `final readonly class`
- boolean: `is` / `has` / `can` 接頭辞（`$isDeleted`, `$isPublic`）
- 日時の ISO 文字列: `{field}Iso` 接尾辞（`$publishedAtIso`, `$deletedAtIso`）

### クラス定数（`const`）

- **SCREAMING_SNAKE_CASE**
- 固定値の集合には使わない → enum を使う
- 許容される用途: マジックナンバー、設定キー文字列、URL テンプレートなど

```php
// ✅ OK: マジックナンバー系定数
public const MAX_RETRIES = 3;
public const API_PREFIX = '/api/v1';

// ❌ NG: 値の集合を定数クラスで表現
class EntityStatus {
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
}

// ✅ OK: backed enum で表現
enum EntityStatus: string {
    case Draft = 'draft';
    case Published = 'published';
}
```

### Enum case

- **PascalCase**（backed / 純粋 どちらも）

```php
// ✅ OK
enum Role: string { case Admin = 'admin'; case Editor = 'editor'; }
enum Capability { case ManageSchema; case EditContent; }
enum EntityStatus: string { case Draft = 'draft'; case Published = 'published'; }

// ❌ NG
enum EntityStatus: string { case DRAFT = 'draft'; case draft = 'draft'; }
```

---

## PHP — ファイルと名前空間

### 名前空間

```
NeNeRecords\{Domain}\{ClassName}
NeNeRecords\Tests\{Domain}\{ClassName}
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| ドメイン名は PascalCase | `NeNeRecords\Entity\` | `NeNeRecords\entity\` |
| テストは `Tests` サブ名前空間 | `NeNeRecords\Tests\Entity\` | `NeNeRecords\Entity\Tests\` |

### ファイル名

- クラス名と完全一致（大文字小文字含む）
- 例外: 移行・マイグレーション（Phinx）は `YYYYMMDDHHMMSS_snake_description.php`

---

## PHP — テスト命名

### テストクラス

```
{Target}Test.php
```

| 対象 | クラス名例 |
| --- | --- |
| ユースケース単体 | `CreateEntityUseCaseTest` |
| PDO リポジトリ | `PdoEntityTypeRepositoryTest` |
| HTTP エンドポイント | `EntityHttpTest`, `EntityFilterHttpTest` |
| ドメインロジック（enum等） | `RoleTest`, `CapabilityResolverTest` |

### インメモリダブル

```
InMemory{Entity}Repository.php
```

| 規則 | 例 |
| --- | --- |
| `InMemory` 接頭辞 + エンティティ名 + `Repository` | `InMemoryEntityRepository` |
| 本番インターフェースを実装 | `implements EntityRepositoryInterface` |

### テストメソッド

```
test{DescriptionInCamelCase}(): void
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| `test` 接頭辞 必須 | `testReturnsOutputWithNewId` | `it_returns_output` |
| camelCase | `testAssignsSequentialIds` | `test_assigns_sequential_ids` |
| 動詞で意図を表す | `testThrowsWhenEntityTypeNotFound` | `testEntityTypeNotFound` |
| セルフドキュメンティング（ `should` 不要） | — | `testShouldReturnId` |

---

## TypeScript — ファイル命名

| アーティファクト | パターン | 例 | 禁止例 |
| --- | --- | --- | --- |
| React コンポーネント | `PascalCase.tsx` | `EntityTypeCreateForm.tsx` | `entityTypeCreateForm.tsx` |
| カスタムフック | `use-kebab-case.ts` | `use-manage-entity-types-page.ts` | `useManageEntityTypesPage.ts` |
| エンティティスライス全ファイル | `kebab-case.ts` | `api-types.ts`, `query-keys.ts`, `mapper.ts` | `apiTypes.ts`, `queryKeys.ts` |
| フィーチャースライス全ファイル | `kebab-case.ts` / `PascalCase.tsx` (UI のみ) | `use-create-entity-type-form.ts`, `EntityTypeCreateForm.tsx` | — |
| Storybook | `{ComponentName}.stories.tsx` | `Button.stories.tsx` | — |
| テスト | `{target}.test.ts` / `{Component}.test.tsx` | `mapper.test.ts`, `EntityTypesPage.test.tsx` | — |
| エンティティスライスディレクトリ | `kebab-case` (OpenAPI タグに一致) | `entity-type/`, `bool-field/` | `EntityType/`, `entityType/` |
| フィーチャーディレクトリ | `kebab-case` | `manage-entity-types/` | `ManageEntityTypes/` |

---

## TypeScript — 型とインターフェース

### コンポーネント Props

```typescript
// ファイル内 同一ファイルに定義
export interface {ComponentName}Props {
  ...
}
```

- 必ず `interface`（`type` 不可）
- named export のみ（default export 禁止）

### モデル型（UI 読み取りモデル）

```typescript
// entities/{resource}/model.ts
export interface EntityType { ... }
export interface EntityTypeList { ... }
export interface CreateEntityTypeInput { ... }
export type UpdateEntityTypeInput = CreateEntityTypeInput
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| 単一リソース: PascalCase、接尾辞なし | `EntityType`, `Tag` | `EntityTypeModel`, `IEntityType` |
| リスト型: `{Resource}List` | `EntityTypeList`, `EntityList` | `EntityTypes`, `EntityTypeListResponse` |
| 作成入力: `Create{Resource}Input` | `CreateEntityTypeInput` | `EntityTypeCreatePayload` |
| 更新入力: `Update{Resource}Input` | `UpdateEntityTypeInput` | `EntityTypeUpdatePayload` |
| `interface` for オブジェクト型; `type` for ユニオン・mapped type | — | — |

### API DTO 型（wire 型）

```typescript
// entities/{resource}/api-types.ts
export interface EntityTypeDto { ... }
export interface EntityTypeListDto { ... }
export interface CreateEntityTypeDto { ... }
export interface UpdateEntityTypeDto { ... }
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| `Dto` 接尾辞 必須 | `EntityTypeDto` | `EntityTypeApiType`, `EntityTypeResponse` |
| Request body: `Create{Resource}Dto` / `Update{Resource}Dto` | `CreateEntityTypeDto` | `CreateEntityTypeRequest` |
| Response body: `{Resource}Dto` | `EntityTypeDto` | `EntityTypeResponse` |
| features/ や pages/ から直接 import 禁止 | — | — |

### Branded ID 型

```typescript
// entities/{resource}/ids.ts
declare const entityTypeIdBrand: unique symbol
export type EntityTypeId = number & { readonly [entityTypeIdBrand]: never }
export function toEntityTypeId(value: number): EntityTypeId { ... }
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| 型名: `{PascalCaseResource}Id` | `EntityTypeId`, `TagId`, `BoolFieldId` | `EntityType_Id`, `entityTypeId` |
| brand 変数: `{camelCaseResource}IdBrand` | `entityTypeIdBrand` | `EntityTypeIdBrand`, `ENTITY_TYPE_ID_BRAND` |
| コンストラクタ関数: `to{ResourceId}` | `toEntityTypeId` | `createEntityTypeId`, `asEntityTypeId` |

### ユニオン型・文字列 Enum

```typescript
// entities/{resource}/enum.ts または shared/lib/enums/{name}.ts
export type EntityStatus = 'draft' | 'published' | 'archived'
export type UserRole = 'admin' | 'editor'
export type Capability = 'manage_schema' | 'manage_settings' | ...
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| 型名は PascalCase | `EntityStatus`, `UserRole` | `ENTITY_STATUS` |
| TS では string ユニオン or `as const` オブジェクトを使う（`enum` キーワード非推奨） | — | `enum EntityStatus { Draft = 'draft' }` |
| 値文字列は snake_case（API との一致） | `'manage_schema'`, `'draft'` | `'manageSchema'`, `'Draft'` |

---

## TypeScript — 変数と関数

### 変数・定数

| スコープ | パターン | 例 | 禁止例 |
| --- | --- | --- | --- |
| モジュールスコープ不変定数 | `SCREAMING_SNAKE_CASE` | `STORAGE_KEY`, `DEFAULT_LIST_PARAMS`, `EDITOR_CAPABILITIES` | `storageKey`, `defaultListParams` |
| 関数内ローカル変数 | `camelCase` | `queryClient`, `listQuery`, `editTarget` | `QueryClient`, `list_query` |
| 関数・メソッド名 | `camelCase` | `createEntityType`, `requestEdit` | `CreateEntityType` |

### Named export のみ

```typescript
// ✅ OK
export function mapEntityTypeDtoToModel(...) { ... }
export const entityTypeKeys = { ... }
export interface EntityType { ... }

// ❌ NG
export default function mapEntityTypeDtoToModel(...) { ... }
```

---

## TypeScript — エンティティ層固有

### Mapper 関数

| 方向 | パターン | 例 | 禁止例 |
| --- | --- | --- | --- |
| DTO → モデル（単件） | `map{Resource}DtoToModel` | `mapEntityTypeDtoToModel` | `entityTypeDtoToModel`, `toEntityType` |
| DTO → モデル（リスト） | `map{Resource}ListDtoToModel` | `mapEntityTypeListDtoToModel` | `mapEntityTypesDtoToModel` |
| 作成入力 → DTO | `mapCreateInputToDto` | `mapCreateInputToDto` | `mapCreateEntityTypeInputToDto` |
| 更新入力 → DTO | `mapUpdateInputToDto` | `mapUpdateInputToDto` | `mapUpdateEntityTypeInputToDto` |
| Attach 入力 → DTO | `mapAttachInputToDto` | `mapAttachInputToDto` | `mapAttachEntityRelationInputToDto` |
| Detach 入力 → DTO | `mapDetachInputToDto` | `mapDetachInputToDto` | — |
| 特殊変換（複数 DTO 型あり） | `map{Qualifier}{Resource}DtoToModel` | `mapSettingAdminItemDtoToModel`, `mapPublicSettingItemDtoToModel` | — |

> **根拠:** Input → DTO 方向のマッパーは、モジュールコンテキスト内で `create`/`update`/`attach` だけで一意に識別できる。リソース名の重複は冗長。ただし同一モジュールに複数の DTO 型（admin/public）が存在する場合は修飾語を付ける。

### Query Key ファクトリ

```typescript
// entities/{resource}/query-keys.ts
export const {camelCaseResource}Keys = {
  all: ['{plural-kebab-resource}'] as const,
  lists: () => [...{camelCaseResource}Keys.all, 'list'] as const,
  list: (params: ...) => [...{camelCaseResource}Keys.lists(), params] as const,
  details: () => [...{camelCaseResource}Keys.all, 'detail'] as const,
  detail: (id: ...) => [...{camelCaseResource}Keys.details(), id] as const,
}
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| 変数名: `{camelCaseResource}Keys` | `entityTypeKeys`, `boolFieldKeys` | `EntityTypeKeys`, `ENTITY_TYPE_KEYS` |
| `all` キーは plural kebab-case の文字列配列 | `['entity-types']` | `['entityType']`, `['entity_types']` |
| メソッド名: `lists`, `list`, `details`, `detail` | — | `getList`, `fetchAll` |

### Query / Mutation フック

```typescript
// entities/{resource}/queries.ts
export function use{Resource}(...): UseQueryResult<Model, AppError> { ... }
export function use{Resource}List(...): UseQueryResult<ModelList, AppError> { ... }

// entities/{resource}/mutations.ts
export function useCreate{Resource}(): UseMutationResult<...> { ... }
export function useUpdate{Resource}(): UseMutationResult<...> { ... }
export function useDelete{Resource}(): UseMutationResult<...> { ... }
export function useAttach{Resource}(): UseMutationResult<...> { ... }
export function useDetach{Resource}(): UseMutationResult<...> { ... }
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| `use` 接頭辞 必須 | `useEntityType`, `useEntityTypeList` | `fetchEntityType`, `getEntityTypes` |
| 単件: `use{Resource}` | `useEntityType(id)` | `useGetEntityType(id)` |
| リスト: `use{Resource}List` | `useEntityTypeList()` | `useEntityTypes()` |
| 変更: `use{Verb}{Resource}` | `useCreateEntityType`, `useUpdateEntityType` | `useEntityTypeCreate` |

---

## TypeScript — コンポーネントとフック

### コンポーネント

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| 関数名: PascalCase | `EntityTypeCreateForm`, `ManageEntityTypesView` | `entityTypeCreateForm` |
| Props インターフェース: `{ComponentName}Props` | `EntityTypeCreateFormProps` | `Props`, `EntityTypeCreateFormPropTypes` |
| default export 禁止 | `export function Button(...)` | `export default function Button(...)` |

### フィーチャーフック

```
use-{feature-purpose}.ts   (kebab-case ファイル名)
use{FeaturePurpose}        (camelCase 関数名 - use 接頭辞必須)
```

| 規則 | 例 |
| --- | --- |
| ファイル: `use-{kebab}.ts` | `use-manage-entity-types-page.ts` |
| 関数: `use{PascalCase}` | `useManageEntityTypesPage` |

### Storybook

| 規則 | 例 |
| --- | --- |
| ファイル: `{ComponentName}.stories.tsx` | `Button.stories.tsx` |
| `meta.title`: `'Primitives/Button'` または `'Components/ConfirmDialog'` | — |
| Story 名: PascalCase | `Default`, `Disabled`, `DangerVariant` | — |

---

## TypeScript — テスト命名

### ファイル

| 対象 | パターン | 例 |
| --- | --- | --- |
| mapper / query-key / pure util | `{file}.test.ts` (colocated) | `mapper.test.ts`, `query-keys.test.ts` |
| コンポーネント / フィーチャー | `{ComponentName}.test.tsx` | `EntityTypesPage.test.tsx` |
| ページ | `{PageName}.test.tsx` | `PublicBrowsePage.test.tsx` |

### describe / it / test

```typescript
describe('{ComponentOrModule}', () => {
  it('{動詞で始まる意図説明}', () => { ... })
})
```

| 規則 | 例 | 禁止例 |
| --- | --- | --- |
| `describe` はコンポーネント名またはモジュール名 | `describe('mapEntityTypeDtoToModel', ...)` | — |
| `it` は動詞で始まる | `it('maps id to branded EntityTypeId', ...)` | `it('should map id', ...)` |
| `should` 不要 | `it('returns empty list when...', ...)` | `it('should return empty list', ...)` |

---

## クロスカッティングルール

### API フィールド名（JSON / OpenAPI）

- **snake_case** (例: `entity_type_id`, `is_deleted`, `published_at`)
- PHP DTO プロパティ (camelCase) と JSON フィールド (snake_case) の変換は Handler 層で行う
- TypeScript DTO (api-types.ts) のフィールド名は API JSON と完全一致（snake_case）

### API パス（ルート）

- **kebab-case の複数形** (例: `/api/v1/entity-types`, `/api/v1/field-defs`)
- パスパラメータ: `{id}` のみ（snake_case 禁止）
- OpenAPI `operationId`: camelCase 動詞 + 単数/複数エンティティ名（例: `listEntityTypes`, `createEntityType`）

### テーブル名・カラム名（SQL）

- テーブル名: **snake_case の複数形** (例: `entity_types`, `text_fields`)
- カラム名: **snake_case** (例: `entity_type_id`, `is_deleted`, `published_at`)
- 外部キー: `{参照テーブル単数形}_id` (例: `entity_type_id`)
- ソフトデリート: 常に `is_deleted` (bool) + `deleted_at` (nullable datetime)

### マイグレーションファイル・クラス

```
database/migrations/YYYYMMDDHHMMSS_snake_description.php
class CreateEntityTypesTable extends AbstractMigration  ← PascalCase
```

---

## 違反チェックリスト

PR をセルフレビューする際に確認する。すべて ✅ でなければ merge 不可。

### PHP

- [ ] 新しいクラスは役割に合った接尾辞を持つ（`Handler`, `UseCase`, `Input`, `Output`, `Repository`, `Item` 等）
- [ ] リストアイテム DTO は `List{Entity}Item` パターン（`{Entity}ListItem` 不可）
- [ ] 固定文字列集合に `final class` + 定数を使っていない（`enum` を使う）
- [ ] Enum の case 名は PascalCase
- [ ] リポジトリメソッドは `find*` / `exists*` / `save` / `update` / `softDelete` / `count*` を使う
- [ ] テストクラスは `{Target}Test`、メソッドは `test{CamelCase}(): void`
- [ ] `InMemory*Repository` は本番 interface を実装している
- [ ] `declare(strict_types=1)` が全 PHP ファイルにある

### TypeScript

- [ ] コンポーネントファイルは `PascalCase.tsx`、フックファイルは `use-kebab-case.ts`
- [ ] Input→DTO マッパーは `mapCreateInputToDto` / `mapUpdateInputToDto`（リソース名重複なし）
- [ ] DTO→モデルマッパーは `map{Resource}DtoToModel` パターン
- [ ] Query key ファクトリは `{camelCaseResource}Keys`
- [ ] モジュールスコープ定数は `SCREAMING_SNAKE_CASE`
- [ ] Props インターフェースは `{ComponentName}Props`
- [ ] DTO 型は `{Resource}Dto` / `Create{Resource}Dto` パターン（`api-types.ts` に限定）
- [ ] Branded ID は `{Resource}Id` 型 + `to{ResourceId}(value)` コンストラクタ
- [ ] default export を使っていない

---

## 関連ドキュメント

- PHP 全体: [`backend-standards.md`](./backend-standards.md)
- TypeScript 全体: [`frontend-standards.md`](./frontend-standards.md)
- コーディング標準索引: [`coding-standards.md`](./coding-standards.md)
- NENE2 継承: [`../inheritance-from-nene2.md`](../inheritance-from-nene2.md)

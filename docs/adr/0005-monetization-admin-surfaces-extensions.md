# ADR 0005: 収益モデルの開放度・管理画面の責務分離・拡張アーキテクチャ

## Status

accepted

## Context

NeNe Records は AI-native な型付きエンティティ CMS（軽量な WordPress 代替）。マルチテナント SaaS（`nene-records.com`・subdomain＋独自ドメイン＋自動TLS）として稼働し、同時に self-host 可能（git clone・将来 zip インストーラ）。運営は実質ソロ（ayane）＋AI エージェントで限界開発コストはほぼゼロ、段階は初期（有料顧客 ≈ 0）。

市場ペルソナ討論 r1–r7（#535…#616）と**経営ペルソナ特別会**（OSS創業者／個人開発者／VC／連続起業家／CFO・法務／パートナーシップの6名を独立評価）、および管理画面の権限分離調査から、相互依存する3つの判断が浮かんだ:

1. 収益モデルをどこまで「開放」するか（A 誰でも開放 / B 難読化で囲い込み / C コア無制限OSS＋"課金"だけ private）。
2. テナント管理者（`/admin`）と運営（`/superadmin`）の責務をどう分けるか（特に plan/独自ドメイン/課金）。
3. private/commercial/ayane 専用機能を fork せず足す「拡張の仕組み」をどう設計するか。

### 既存事実（コード実測）

- **エンタイトルメント seam 実装済み**（`EntitlementResolverInterface`・既定 `UnlimitedEntitlementResolver`・#618）。テナント別上限を*データ*として解決し、**既定は無制限**。
- **モジュール機構** = `ServiceProviderInterface` を `ApplicationServiceProvider` に**ハードコード列挙**して合成（routes / exception handlers も集約）。**NENE2 は composer の `path` リポジトリ**で注入。
- **フロント** = block/widget/field-type は型付きディスパッチ（クローズドな registry）。router/ナビは直書き。3面構成（apex/公開 `/`、テナント管理 `/admin`、運営 `/superadmin`）。
- **ロール** = Superadmin（全 capability・`org_id=null`・横断・`ManageOrganizations` を持つ唯一）／Admin（`ManageOrganizations` 以外・自org）／Editor（コンテンツ）。強制チェーン: OrgResolver → AdminApiAuth → Capability。
- **製品 identity** = 型付き・安全・第一者・**任意JS不可 / Elementor 不採用 / カスタムページはサンドボックス**。

### ペルソナの示唆

経営6名は **B（難読化）を全会一致で却下**し、**near-term は C を全会一致で選択**。共通見解: 堀は課金コードではない／self-host 無制限こそファネル／≈0客で moat を作り込むのは先延ばし／初売上はコンシェルジュ／「金はサービスで取る（フラグでなく）」。割れたのは「overlay/ライセンスを今正式化するか」と「将来 controlled-A（プラットフォーム/チャネル）を狙うか」。

## Decision

### D1. 収益開放度＝C（コア無制限OSS＋"課金"だけ private モジュール）。A（無償 turnkey）と B（難読化）は却下。

- **コア = permissive OSS、self-host は常に無制限**（seam 既定 Unlimited・enforcement 地点に上限を焼き込まない）。
- **"金を取る"層**（plan→上限 catalog／PlanBased、Stripe 課金、サブスク、`/account` self-serve、運営の課金コンソール、本番価格表）= **ayane build にだけ載る private モジュール**。
- 線引きは「**課金するか**」であって「上限を課すか」ではない。**quota の enforcement（seam＋plan→上限 catalog）はコアに置いてよい**（自前ホストの内部マルチテナントに有用・競合脅威でない）。**支払いを取る部分だけ private。**
- **C-lite で始める**: private 層は最小に保ち、重い package 化/商用ライセンスを*今は正式化しない*。初売上は**コンシェルジュ**（Stripe 支払いリンク＋運営が plan を手動設定）。売りは**サービス**（managed/独自ドメイン/サポート）であってフラグ解除ではない。
- **B 却下**: 法的に守れず・採用を殺し・負ROI・ayane 自身も遅くなる。
- **A（無償 turnkey）却下**: 自分の労力の上に競合のホスト事業を乗せるだけ。*将来のライセンス付きプラットフォーム/パートナー* は別の繰延オプション（D4 参照）。

### D2. ライセンス＝今は permissive、トリガーで前方適用。

- コアは当面 **Apache-2.0 / MIT**（採用最大化）。
- 判断を今記録（トリガー）: **(a) 有料顧客が出た or (b) 資金付き競合が rival ホストを立てた**ときに、**private/commercial モジュールへ BSL-1.1「競合ホスト SaaS 禁止」条項**を前方適用。≈0 traction での過剰適用は避ける。
- 保護は**「ライセンス＋package 分離」**で行い、**難読化に頼らない**。

### D3. 管理画面の責務分離。

- 3面を維持: **apex/公開 `/`**（認証なし）、**テナント管理 `/admin`**（自org）、**運営 `/superadmin`**（横断）。
- **スコープ規則**: テナント系エンドポイントは org を **JWT/解決コンテキスト由来**で決め、**任意の org id を受け取らない**（横取り防止）。運営系は `{id}` 指定（横断・authoritative override）。
- **新・テナント self-serve 面 `/admin/account`（＋ `/api/v1/account*`）**: テナント Admin が自分の plan/使用量を見る、自分の独自ドメインを管理（entitlement 次第）、課金/サブスク/請求書。org slug は**運営のみ**（サブドメイン変更＝破壊的）、org 名はテナント可（任意）。
- **新 capability `ManageAccount`**（Admin 限定・自org・Editor 不可）。`ManageOrganizations` は運営/横断のまま。
- **entitlement gate の位置**: gate は**テナント self-serve 経路**に置く（テナントがドメイン追加/アップグレード → 自分の plan で判定 → 402/アップグレード導線）。運営の override 経路は authoritative（運営は plan を上げてから割当）。※ #618 の gate は今 運営経路にあるので、`/account` 実装時にテナント経路へ移設/複製。
- テナント `/account` の**枠**はコアに置いてよいが、課金の**中身**は private モジュールが供給（無ければ inert）。

#### 責務マトリクス（争点＝org identity / plan / domain / billing）

| 機能 | Editor | テナント Admin（`/admin/account`） | 運営 superadmin（`/superadmin`） |
| --- | :--: | --- | --- |
| 自org の plan / 使用量を見る | ✗ | ✓ 閲覧 | ✓ 全org |
| plan の変更（up/down） | ✗ | △（self-checkout or 相談導線・private 提供） | ✓ 割当・comp・override |
| 独自ドメイン 追加/検証 | ✗ | △（self＋DNS検証 or 申請） | ✓ 割当・承認・対応 |
| 課金（サブスク/請求/支払方法） | ✗ | ✓ 自分の請求 | ✓ 徴収・返金・全サブスク |
| org 名 / slug | ✗ | 名前=△ / **slug=運営のみ** | ✓ |
| テナント 停止/削除/作成 | ✗ | ✗ | ✓ |
| プラットフォーム設定 / data-migration | ✗ | ✗ | ✓ |
| コンテンツ/スキーマ/メディア/外観/自org ユーザー | コンテンツのみ | ✓ | ✓ |

### D4. 拡張アーキテクチャ＝型付き deploy-time モジュール機構。WordPress 式ランタイムプラグインは不採用。

- **WP 式ランタイムプラグイン（任意コードを実行時投入・ユーザー有効化）を却下**: 型付き・安全・任意JS不可の identity と矛盾し、WP の脆弱性/品質問題の震源を持ち込む。
- **採用**: 既存の ServiceProvider＋seam を正式なモジュール機構へ昇格:
  - **拡張点 = コアの型付き interface（seam）**。`EntitlementResolver` が雛形。必要に応じ `BillingGateway`/`SubscriptionGateway`、フロントの route/nav/設定パネル contribution registry を追加。拡張は**interface を実装**し、core を patch しない。
  - **モジュール = ServiceProvider を持つ composer package**（DI サービス/routes/exception handlers/seam バインドを登録）。
  - **モジュールレジストリ**: `ApplicationServiceProvider` の直書きリストを、コア標準モジュール＋*インストール済みの任意/private モジュール*を合成するレジストリへ一般化（無ければ permissive OSS のまま）。
  - **private/commercial/ayane 専用モジュール = 別 private composer package**を **path リポジトリ**（NENE2 と同方式）＋ Dockerfile context で注入。ayane build にだけ載る。公開 repo は core＋seam＋無制限既定のみ。
  - **フロント**: 型付きディスパッチ registry を維持。router/nav/設定を **contribution registry** へ一般化し、モジュールが core を編集せず画面要素を足せるように。private UI は build-time include か capability/entitlement で lazy。
  - **信頼ティア**: 拡張は常に**信頼された deploy-time の型付きコード**（ayane or 認定パートナー）。ユーザーアップロードの任意コードは不可。将来のサード解放も「ティア化された信頼＋型付き」のみ。
- **課金 = 第1号 private モジュール**（`nene-records-commercial`）。これを作ること自体が (1) モジュールレジストリ、(2) payment seam、(3) private package 注入経路、を生む forcing function。

### D5. Stripe 決済の土台＝用意する。ただし private モジュール内・コアの payment seam の裏。

- コアに `BillingGateway`/`SubscriptionGateway` interface。Stripe 実装（webhook で subscription↔plan、顧客ポータル、税/JCT/インボイス）は private モジュール。
- コンシェルジュ（支払いリンク＋手動 plan）は今すぐ並行で回す。Stripe はそれを self-serve に昇格。**初売上を Stripe 完成まで待たない。**

## Consequences

**利点**
- 単一コードベース（fork 無し）。self-host は無制限のまま（採用・好意）。ayane は*本物の*商用機能を便利に使える（crippled でない）。他人は private モジュールを持たないので有料SaaSを turnkey で再現できない（難読化ゼロ）。将来の controlled-A/パートナーチャネルは seam を多operator対応に保てば*再platform でなく config*。型付き・安全の identity を維持。

**コスト・後続作業**
- リファクタ: provider 直書き → モジュールレジストリ。`BillingGateway` ＋ フロント contribution registry の定義。
- 新規: `/api/v1/account*`（テナント self-serve・org は context 由来）＋ capability `ManageAccount` ＋ `/admin/account` UI 枠。
- private package `nene-records-commercial`（PlanBased＋plan→上限 catalog＋Stripe＋`/account` 中身＋運営課金コンソール）を path repo＋Docker context で注入。
- entitlement gate をテナント self-serve 経路へ移設/複製。
- private モジュールと速い公開 core の**鍵漏洩/ドリフト**リスク → private 面を最小化＋pre-commit secret scan。
- ライセンス判断は記録済み。BSL のモジュール適用はトリガー時のみ。

**非ゴール（現時点）**
- 有料顧客が出る前の self-serve Stripe チェックアウト。テナント別の細粒度 limit エディタ（plan＋catalog で足りる・org 別 override は繰延）。WP 式プラグイン市場。サードの非信頼拡張。A の開放。

**順序**
1. 本 ADR（設計の正典化）。
2. コアの拡張対応化（モジュールレジストリ＋`BillingGateway` seam＋フロント contribution registry）＝小。
3. 第1号 private モジュール `nene-records-commercial`（PlanBased＋Stripe＋`/account`＋コンソール）。コンシェルジュ売上は並行で即開始可。

## Related

- Issue: `#619`
- 系譜: 市場ペルソナ討論 r1–r7（#535 … #616・特に r7 = `docs/review/ux-market-personas-discussion-r7-2026-06-26.md`）、経営ペルソナ特別会（本セッション）、エンタイトルメント seam #617 / PR #618、signup レート制限 #613 / PR #614。
- memory: `entitlement-monetization-architecture` / `managed-saas-onramp-direction`。
- Supersedes: none
- Superseded by: none

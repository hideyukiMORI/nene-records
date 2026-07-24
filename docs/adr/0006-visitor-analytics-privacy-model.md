# ADR 0006: Visitor analytics privacy model

## Status

proposed

## Context

`access_logs`（`AccessLogMiddleware` + `PdoAccessLogRepository`・#69/#70）は現状
`method / path / status_code / duration_ms / accessed_at / access_date`（＋`organization_id`）だけを持ち、
**クライアント IP・User-Agent・Referer・query を一切記録していない**。

調査（2026-07-24）の結論:

- この「PII を持たない」状態は **明文化された設計判断ではない**。#69/#70 は Phase 5 の機能タスク（日次
  request 数＋平均レイテンシのダッシュボード）で、フィールドは*その集計に必要な分だけ*選ばれた。IP/UA/referer/query は
  「privacy のため意図的に外した」のではなく最初からスコープ外。ADR も設計コメントも無い。
- ただし製品は事実上 **生 IP をどこにも永続化していない**（comment のレート制限で `REMOTE_ADDR` を
  一時的にキーへ使うだけで DB 保存はしない）。＝実態はプライバシー寄り。

施主裁定により、ユニーク訪問者・流入元・キャンペーン計測などの**高機能化（Path B）を実装する**（#1007）。
これは**製品初の「訪問者由来データの永続化」**であり、NeNe Records は**マルチテナント SaaS**（全 org 横断）なので、
visitor IP/UA を素で持つと個人情報保護法／GDPR／保持期間／同意の論点が全テナントに一斉に発生する。
よって *privacy by design* をここで明文化し、以降の実装を縛る。

計測の producer は 2 系統ある。ユニーク計数を両者で一致させるため、ハッシュのレシピを本 ADR で固定する:

1. **Records SSR / API（Path B・本リポジトリ）** — `access_logs` に visitor 派生列を足す。
2. **静的 LP ビーコン（hub 管轄・#1008）** — HETEML docroot の flat HTML（`/invoice-lp` `/clear-lp`
   `/deal-lp` `/contact` `/inquiry`）は Apache 直配信で `access_logs` を通らないため、別系統で計測する。

## Decision

### D1. 生の PII を永続化しない

生 IP・完全な UA・完全な query・完全な Referer URL は **DB に書かない**。IP は算出時にメモリ上でのみ使い、
ハッシュ化後に破棄する。

### D2. ユニーク訪問者＝日次ソルトのハッシュ

```
visitor_hash = lowercase_hex( sha256( daily_salt_bytes || client_ip_utf8 || ":" || org_id_decimal ) )
```

- **daily_salt**: 32 バイト（256-bit）のランダム値。**カレンダー日ごとに 1 つ**生成し、専用テーブル
  `analytics_salts(salt_date DATE PK, salt VARBINARY(32), created_at DATETIME)` に保存。日付基準は
  `access_date` と同じ TZ（アプリ設定 TZ・ayane は JST）。初回アクセス時に無ければ生成（get-or-create）。
- **client_ip**: `NeNeRecords\Http\ClientIp`（既存・X-Forwarded-For 対応）で解決した実 IP の文字列。
- **org スコープ**: `organization_id` を混ぜる → 同一訪問者がテナント横断で相関しない（マルチテナント分離）。
- **日跨ぎ不可**: ソルトは日ごとにローテし、保持期間後に破棄する（D6）。よって過去日の hash を既知 IP で
  総当たり再識別することはできない（ソルトが残らない）。ユニーク計数は `COUNT(DISTINCT visitor_hash)` を
  `access_date` 単位で行う（＝「日次ユニーク」。ソルトが日次なので週/月ユニークは定義上出せない＝プライバシー上の意図的制約）。

### D3. Referer はホストのみ

`referer_host`（VARCHAR）に **ホスト名だけ**を保存する。完全な Referer URL は path/query に PII を含みうるため保存しない。

### D4. query は保存しない・許可リストのみ抽出（utm_* ＋ ref）

生 query 文字列は保存しない（email・token・検索語などの PII 温床）。**許可リスト**
`utm_source / utm_medium / utm_campaign / ref` だけを抽出し、長さ制限・サニタイズのうえ保存する。
`ref` は営業導線の attribution 用（署名リンクのキャンペーン識別子・非 PII）。LP ビーコン（D8）は
`location.search` をそのまま送ってよいが、**サーバはこの許可リスト以外を破棄する**（生 query は永続化しない）。

### D5. UA は素で持たず「分類」だけ

生 UA は保存しない。`client_type`（enum: `bot` / `mobile` / `desktop` / `other`）＋ `is_bot`（bool）を導出して保存する。
fingerprint 面を最小化しつつ bot 除外を可能にする。生 UA が必要な用途は将来の別 opt-in（本 ADR のスコープ外）。

### D6. org 単位 opt-in・既定 OFF・保持 prune

- SettingDef `analytics_visitor_tracking`（`bool` / 既定 `false` / `is_public` 0）。
  **OFF のときミドルウェアは現状と完全に同一挙動**（hash/referer/utm/ua 分類を一切作らない）＝既存 org の挙動は不変。
  ON のときのみ D2–D5 を計算する。テナントが自らのプライバシーポリシー／同意運用に合わせて収集を制御する。
- **保持期間**: visitor 派生列（`visitor_hash` 等）を持つ行は既定 **180 日**で prune（将来 org 可変）。
  `analytics_salts` も同期間で prune。匿名の日次ロールアップは無期限保持可。日次の prune ジョブを回す。
- **同意連携（注記）**: GDPR 運用の org は公開 SSR の同意トラック（`analytics_consent_default` 等）と連動して
  opt-in をゲートすべき。Cookie を使わない IP ハッシュは厳密には Cookie トラッキングではないが、テナント責任として文書化する。
  既定 OFF が安全側の既定。

### D7. 後方互換

`access_logs` への追加列は**全て NULL 許容**（backfill 不要・既存行は NULL）。既存の集計クエリ
（`aggregateByDate` / `aggregateEntityViews` / `countByDate`）は無改変。新指標（`unique_visitors` /
`top_referrers` / `utm` / `bot_rate`）は集計 API・OpenAPI・MCP に **nullable で追加**（契約安全）。

### D8. 2 producer の一致（hub の LP ビーコンとの整合）

hub 管轄の LP ビーコン（#1008）は**同一の visitor_hash レシピ（D2）**と**同一の `analytics_salts`**を使う。
両者は同じ HETEML Tier A インスタンス（同一 DB）で動くため、ビーコン側は `analytics_salts` から当日ソルトを
直接読んで LP ヒットの hash を計算する → SSR（Path B）と LP のユニーク計数が名寄せ・合算できる。
生 IP 非保存・host-only referer・utm-only の方針も踏襲する。

## Consequences

**得られるもの**
- 生 PII を持たずに「日次ユニーク訪問者・流入元ホスト・utm キャンペーン・bot 率」を算出できる。
- SSR（Records）と LP（hub ビーコン）でユニーク計数のレシピが一致し、mori 日次メールの 2(3) ソース集約で
  重複なく合算できる。

**コスト・追随作業**
- 新テーブル `analytics_salts`。`access_logs` への追加列＋`INDEX(access_date, visitor_hash)`。
- 新 SettingDef `analytics_visitor_tracking`（seeder カタログ＋既存 org 向け backfill migration の**両方**に足す
  ＝ `PdoDefaultSettingDefsSeeder` の規約）。
- 収集ミドルウェアの分岐（opt-in ON 時のみ）。日次ソルト get-or-create ＋保持 prune ジョブ（cron・`.sh` ラッパー越し）。
- 集計 API／ダッシュボード／OpenAPI／MCP の nullable 追加。
- export CLI（`tools/export-access-summary.php`）＋ `AccessLogMiddleware` の `'/'` 除外解除（トップ訪問を数える）。

**本番反映のガード**
- Tier A への migration＋cron 設置＋ayane（org 1）の opt-in ON は**実装＋テスト完了→施主最終 GO**の順。
- opt-in 既定 OFF なので、既存の全 org・全インスタンスの挙動は本変更で変わらない。

## Related

- Issue: `#1007`（epic）, `#1008`（LP ビーコン・hub 管轄）
- PR: `#000`
- Supersedes: none
- Superseded by: none

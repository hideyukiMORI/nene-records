# 引継ぎ — Claude.ai へ MCP を Connector 登録する（検証用ブリッジ）

最終更新: 2026-06-19 / 関連ブランチ: `feat/mcp-bridge`（PR #438・未マージ） / Issue #437

ローカルの NeNe Records の MCP ツール（テーマ系）を **Claude.ai の Custom Connector** に繋ぐ作業。`docs/mcp/tools.json` は**カタログでありサーバではない**ため、それを実際に喋る**薄いリモート MCP サーバ（ブリッジ）**を作り、ローカル起動＋cloudflared トンネルで一時公開して登録する、という方針。**ブリッジ本体はローカルでハンドシェイクまで動作確認済み**。中断時点は「トークン取得まで完了、トンネル＆Claude.ai 登録はこれから」。

---

## 1. いまどこまで来たか（状態）

- ✅ **ブリッジ実装＋ローカル動作確認**: `mcp-bridge/`（Node + `@modelcontextprotocol/sdk`・Streamable HTTP）。`initialize → notifications/initialized → tools/list` が通り、`/health` も OK。`tools/call` は NeNe API へプロキシ。
- ✅ **手順書**: `docs/integrations/mcp-connector.md`。
- ✅ **API トークン取得を確認**: 下記シード資格情報で JWT 取得 → `GET /api/v1/themes` が `{"items":[]}` を返すところまで確認済み。
- ⬜ **cloudflared 未導入**（`which cloudflared` → なし）。これが次の関門。
- ⬜ **Claude.ai への登録は未実施**。
- ⚠️ **`previewTheme`/`createTheme` 等はまだ見えない**：それらは別PR（#434）で main 未マージのため、`main` 起点の `tools.json` には read の `listThemes`/`getTheme` のみ。

## 2. すぐ再開できる runbook

### 2.1 資格情報（シード・ローカル開発用）

`database/migrations/20260525000000_seed_default_users.php` 由来。**パスワードは `nene1234`**。

| email | role |
| --- | --- |
| `admin@nene-records.local` | superadmin |
| `editor@nene-records.local` | editor |
| `admin@local.dev` | admin（別シード） |

> 本番資格情報ではない。commit/ログに出さない運用は維持。

### 2.2 トークン取得（確認済みコマンド）

```bash
curl -s -X POST http://localhost:18082/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@nene-records.local","password":"nene1234"}' \
  | python3 -c 'import sys,json;print(json.load(sys.stdin)["token"])'
```

（落とし穴：`<admin>`/`<pw>` のプレースホルダのまま実行すると 401 → `KeyError: 'token'`。実値を入れる。）

### 2.3 ブリッジ起動

```bash
cd mcp-bridge
npm install                                   # 初回のみ（123 packages）
NENE_API_TOKEN='<上のtoken>' npm start         # → http://localhost:8765/mcp（read theme ツールのみ）
# 確認: curl -s http://localhost:8765/health → {"ok":true,"tools":2}
```

env: `NENE_API_BASE`(既定 localhost:18082) / `MCP_PORT`(8765) / `MCP_PATH`(/mcp) / `MCP_READONLY`(1=readのみ) / `MCP_TOOLS`(themes|all) / `MCP_BRIDGE_SECRET`(任意 `?key=` ガード)。

### 2.4 公開（次の作業：cloudflared を入れてから）

```bash
# 未導入なので先にインストール（WSL/Linux）:
#   例) cloudflared の公式バイナリ/パッケージを入れる（distro により方法が異なる）
cloudflared tunnel --url http://localhost:8765   # quick tunnel（URLは毎回変わる）
# → https://<random>.trycloudflare.com
```

### 2.5 Claude.ai 登録

Customize > Connectors（ディレクトリ表示は解除）> 「+」> Add custom connector >
URL = `https://<tunnel>.trycloudflare.com/mcp`（**末尾 `/mcp` 必須**）> Add。
`listThemes`/`getTheme` が見えれば成功。終わったら **cloudflared と npm start を停止**。

## 3. アーキテクチャ / 主要ファイル

| 役割 | 場所 |
| --- | --- |
| ブリッジ本体（Streamable HTTP・proxy） | `mcp-bridge/server.mjs` |
| 依存 | `mcp-bridge/package.json`（`@modelcontextprotocol/sdk` / `express`） |
| 手順書 | `docs/integrations/mcp-connector.md` |
| ツールカタログ（プロキシ元） | `docs/mcp/tools.json`（`composer mcp:generate` で再生成） |
| ツールの使い方（エージェント向け） | `docs/theming/claudedesign-mcp-guide.md` |

**動作原理**: ブリッジが `tools.json` を読み、`tools/call` を受けたら `source.{method,path}` に従い `NENE_API_BASE` へ `Authorization: Bearer <token>` 付きでプロキシ。path の `{key}` は引数から展開、GET/DELETE は残りを query、POST/PUT は body。既定は `safety==='read'` かつ theme 系のみ公開。

## 4. 残タスク / 次の一手

1. ⬜ **cloudflared 導入** → quick tunnel で公開（最初の関門）。
2. ⬜ **Claude.ai に登録**して read ツールで疎通確認。
3. ⬜ 必要なら **#434（previewTheme）をマージ** → `tools.json` 再生成不要（既にPRに含む）だが main にマージ後 `composer mcp:generate` 済みの tools.json が main に入る → ブリッジ再起動で `previewTheme` が出る。
4. ⬜ **write ツール**（`createTheme`/`updateTheme`/`deleteTheme`）を使うなら `MCP_READONLY=0`。**ただし無認証トンネルに write を晒さない**：named tunnel ＋ `MCP_BRIDGE_SECRET`、できれば OAuth へ。
5. ⬜ **常時稼働が必要になったら**: VPS に API＋ブリッジを同居デプロイ＋HTTPS＋OAuth（本ブリッジは検証用）。API をローカルのまま MCP だけ VPS に置くのはアンチパターン（到達問題が再発）。

## 5. ハマりどころ（既知）

- **`tools.json` ≠ MCP サーバ**。Connector には「公開到達可能なリモート MCP サーバ URL」が要る。
- **`localhost` は Claude.ai から到達不可**（接続は Anthropic 側サーバ発）。**VPN（Tailscale等）も不可**。公開トンネル or 公開デプロイの二択。
- **URL 末尾 `/mcp`** を忘れない。
- **Streamable HTTP** 必須（`Accept: application/json, text/event-stream`）。古い SSE 専用クライアントは不可。
- **Custom connector が UI に出ない**時: `?directory=true` を外す／プラン（Free だと制限）／Team/Enterprise はメンバー不可（Owner が組織側で追加）。
- Claude.ai 公式要件: `support.claude.com/.../11175166`（remote MCP via custom connectors）。

## 6. 関連 PR / Issue

| # | 内容 | 状態 |
| --- | --- | --- |
| #438 | 本ブリッジ＋手順（`feat/mcp-bridge`） | open |
| #437 | 本件の Issue | open（#438 で close 予定） |
| #436 | ClaudeDesign 向け MCP ガイド | open |
| #434 | `previewTheme`（計算プレビュー）実装 | open |
| #423 | ランタイムテーマ epic（MCP登録→公開反映） | マージ済み一式 |

> テーマ系 MCP の全体像は `docs/theming/runtime-themes.md` / `claudedesign-runtime-themes.md`。

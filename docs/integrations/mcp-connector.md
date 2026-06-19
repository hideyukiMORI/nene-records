# Claude.ai に MCP を登録する（ローカルブリッジ＋トンネル）

> Claude.ai の **Custom Connector** に登録できる**リモート MCP サーバ**を、ローカルのブリッジ＋cloudflared トンネルで公開する手順（#437）。検証から「PC 常設で ClaudeDesign がテーマを CRUD」まで対応。
> 前提: `docs/mcp/tools.json` はツール**カタログ**であってサーバではない。Claude.ai は「公開到達可能なリモート MCP サーバ URL」を要求するため、本ブリッジ（`mcp-bridge/`）でそれを満たす。

## いつ使うか

- **ローカル常設＝自分の PC が起きている間ずっと動かす**用途（ClaudeDesign がテーマを CRUD する）。完全な常時稼働/共有/本番は VPS デプロイ＋OAuth へ（別途）。
- 既定は **read 系ツールのみ**公開（安全側）。write を出すには `MCP_READONLY=0` ＋ `MCP_BRIDGE_SECRET` 必須（§6）。
- 認証は **email/password を `.env` に置いて自動リフレッシュ**が推奨（管理トークンは 24h で失効するため、常設ではこれが必須）。

---

## 0. 必要なもの

- Node 20.6+（`--env-file` 利用のため。確認: `node -v`）
- NeNe Records API が起動（`http://localhost:18082`、`curl -fsS http://localhost:18082/health`）
- `cloudflared`（未導入なら: macOS `brew install cloudflared` / Linux はパッケージ or バイナリ配布）
- 対象 org の **API トークン**（次項）

## 1. 設定（`.env`）

`mcp-bridge/.env.example` を `.env` にコピーして埋める（`.env` は gitignore 済み）。

```bash
cd mcp-bridge
cp .env.example .env
$EDITOR .env
```

最低限:

- `NENE_API_EMAIL` / `NENE_API_PASSWORD` … 対象 org の管理ユーザ。ブリッジがこれで自動ログインし、**401 時に再ログインして失効しない**（常設の肝）。
  - 一回限りでよければ代わりに `NENE_API_TOKEN` を入れてもよい（24h で失効）。
- write を使うなら `MCP_READONLY=0` ＋ `MCP_BRIDGE_SECRET`（後述、未設定だと**起動を拒否**）。

`MCP_BRIDGE_SECRET` の生成: `openssl rand -hex 32`。

## 2. ブリッジを起動

```bash
cd mcp-bridge
npm install              # 初回のみ
npm start                # .env を自動読込（node --env-file-if-exists=.env）
# → [mcp-bridge] obtained a fresh API token
# → [mcp-bridge] listening on http://localhost:8765/mcp → http://localhost:18082 (readonly=false, guard=on)
```

env 一覧:

| 変数 | 既定 | 説明 |
| --- | --- | --- |
| `NENE_API_EMAIL` | （推奨） | 管理ユーザの email。自動ログイン＋再取得に使う |
| `NENE_API_PASSWORD` | （推奨） | 同パスワード |
| `NENE_API_TOKEN` | （任意） | 一回限りの Bearer。email/password の代替（自動更新なし） |
| `NENE_API_BASE` | `http://localhost:18082` | API ベース URL |
| `MCP_PORT` | `8765` | ブリッジの待受ポート |
| `MCP_PATH` | `/mcp` | MCP エンドポイントのパス |
| `MCP_READONLY` | `1` | `1`=read のみ / `0`=write も公開（`MCP_BRIDGE_SECRET` 必須） |
| `MCP_TOOLS` | `themes` | `themes`=テーマ系のみ / `all`=全ツール |
| `MCP_BRIDGE_SECRET` | （空） | 設定すると `?key=<値>` 必須。**write 時は必須**（空なら起動拒否） |

> `NENE_API_EMAIL` も `NENE_API_TOKEN` も無い場合は起動を拒否する。

ローカル確認:

```bash
curl -s http://localhost:8765/health         # {"ok":true,"tools":N}
```

（任意）MCP の動作を GUI で見るなら MCP Inspector:
`npx @modelcontextprotocol/inspector` → URL に `http://localhost:8765/mcp`（Streamable HTTP）。

## 3. cloudflared で公開（HTTPS）

**クイック（最速・URL は毎回変わる）**:

```bash
cloudflared tunnel --url http://localhost:8765
# → https://<ランダム>.trycloudflare.com が払い出される
```

**named tunnel（URL を固定したい場合）**: `cloudflared tunnel login` → `tunnel create` → DNS ルート設定（Cloudflare 上のドメインが必要）。固定ホスト名にできるので Claude.ai に貼り直さずに済む。

> 公開するのは**ブリッジのポート（8765）**であって、`18082` の生 API ではない。

## 4. Claude.ai に登録

1. Claude.ai → **Customize > Connectors**（ディレクトリ表示なら一旦解除）
2. **「+」> Add custom connector**
3. URL に **`https://<トンネル>.trycloudflare.com/mcp`**（**末尾 `/mcp` を忘れない**。`MCP_BRIDGE_SECRET` を使うなら `?key=...` を付ける）
4. （任意）OAuth は使わない（本検証は無認証エンドポイント＋obscure URL）
5. Add → 接続 → ツール（`listThemes`/`getTheme` 等）が見えれば成功

Claude 側から `listThemes` 等を呼ぶと、ブリッジが NeNe API にプロキシして結果を返す。

## 5. 使い終わったら

- `cloudflared` と `npm start` を **Ctrl-C で停止**（公開を閉じる）。
- 必要なら Claude.ai 側のコネクタも削除。

## 6. セキュリティ（重要）

- 既定は **read 系のみ**（`listThemes`/`getTheme`/`getThemeAuthoringGuide`、#434 マージ後は `previewTheme`）。まずはこれで動作確認。
- **write を出すなら必ず `MCP_BRIDGE_SECRET`**：`MCP_READONLY=0` で `createTheme`/`updateTheme`/`deleteTheme` も公開されるが、ブリッジは **secret 未設定だと起動を拒否**する（無認証トンネルへ write を晒さないため）。Claude.ai には URL 末尾を `…/mcp?key=<secret>` で登録する。
- 常設で write を出す場合は **named tunnel（固定 URL）＋ secret** を最低ラインとし、本番は OAuth/VPS へ。
- secret は推測不能な長さに（`openssl rand -hex 32`）。`?key=` はクエリなのでログに残り得る点に留意（より強くするなら OAuth）。
- トークンはブリッジ内（サーバ側）保持・自動更新。クライアントには出ない。
- 常設をやめるときは `cloudflared` と `npm start` を停止すれば即座に公開が閉じる。

## 7. トラブルシュート

- **ツールが 0**: `tools.json` に該当が無い（例 `previewTheme` は #434 未マージだと出ない）。`MCP_TOOLS=all` や `MCP_READONLY=0` で確認。
- **Claude が繋がらない**: URL 末尾 `/mcp` を確認。トンネルが生きているか（`/health` を tunnel URL で叩く）。`Accept: application/json, text/event-stream` を要する Streamable HTTP なので、古い SSE 専用クライアントは不可。
- **ツール呼び出しが 401**: email/password 運用なら自動再ログインで回復する。`NENE_API_TOKEN`（静的）運用だと 24h で失効するので、`.env` を email/password に切り替えて再起動。
- **write ツールが見えない / 起動しない**: `MCP_READONLY=0` かつ `MCP_BRIDGE_SECRET` を設定したか。secret 未設定だと write 時は起動拒否。Claude.ai 側 URL に `?key=<secret>` を付けたか。
- **`Bad Request: no valid session ID`**: 正常（initialize 前の不正アクセス）。

## 関連

- ツールの使い方: [`../theming/claudedesign-mcp-guide.md`](../theming/claudedesign-mcp-guide.md)
- カタログ: [`../mcp/tools.json`](../mcp/tools.json) / 生成: `composer mcp:generate`
- ブリッジ実体: [`../../mcp-bridge/`](../../mcp-bridge/)

# Claude.ai に MCP を登録する（検証用ブリッジ＋トンネル）

> Claude.ai の **Custom Connector** に登録できる**リモート MCP サーバ**を、ローカルのブリッジ＋cloudflared トンネルで一時公開する手順（#437）。
> 前提: `docs/mcp/tools.json` はツール**カタログ**であってサーバではない。Claude.ai は「公開到達可能なリモート MCP サーバ URL」を要求するため、本ブリッジ（`mcp-bridge/`）でそれを満たす。

## いつ使うか

- **検証＝自分の PC が起きている間だけ動けばよい**用途。常時稼働/共有/本番は VPS デプロイ＋OAuth へ（別途）。
- 既定は **read 系ツールのみ**公開（安全側）。書込みは §6 参照（要注意）。

---

## 0. 必要なもの

- Node 20+（確認: `node -v`）
- NeNe Records API が起動（`http://localhost:18082`、`curl -fsS http://localhost:18082/health`）
- `cloudflared`（未導入なら: macOS `brew install cloudflared` / Linux はパッケージ or バイナリ配布）
- 対象 org の **API トークン**（次項）

## 1. API トークンを取る

themes 系は認証必須。ログインで JWT を取得:

```bash
curl -s -X POST http://localhost:18082/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"<admin email>","password":"<password>"}' | python3 -c 'import sys,json;print(json.load(sys.stdin)["token"])'
```

返った `token` を控える（**commit・ログに出さない**）。

## 2. ブリッジを起動

```bash
cd mcp-bridge
npm install
NENE_API_TOKEN='<上のtoken>' npm start
# → [mcp-bridge] listening on http://localhost:8765/mcp → http://localhost:18082
#   公開ツール（既定）: read 系の theme ツールのみ
```

env（任意）:

| 変数 | 既定 | 説明 |
| --- | --- | --- |
| `NENE_API_TOKEN` | （必須） | NeNe API 向け Bearer トークン |
| `NENE_API_BASE` | `http://localhost:18082` | API ベース URL |
| `MCP_PORT` | `8765` | ブリッジの待受ポート |
| `MCP_PATH` | `/mcp` | MCP エンドポイントのパス |
| `MCP_READONLY` | `1` | `1`=read ツールのみ / `0`=write も公開（注意） |
| `MCP_TOOLS` | `themes` | `themes`=テーマ系のみ / `all`=全ツール |
| `MCP_BRIDGE_SECRET` | （空） | 設定すると `?key=<値>` 必須（簡易ガード） |

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

- 既定は **read 系のみ**（`listThemes`/`getTheme`、#434 マージ後は `previewTheme`）。**まずはこれで動作確認**。
- **書込み/破壊系を公開しない**：`MCP_READONLY=0` で `createTheme`/`updateTheme`/`deleteTheme` も出るが、**無認証トンネルに write を晒すのは危険**。出すなら named tunnel＋`MCP_BRIDGE_SECRET`、できれば OAuth/VPS 構成へ。
- トンネル URL 自体が事実上の秘密（不可推測）。検証が終わったら**必ず停止**。
- トークンはブリッジ内（サーバ側）保持。クライアントには出ない。

## 7. トラブルシュート

- **ツールが 0**: `tools.json` に該当が無い（例 `previewTheme` は #434 未マージだと出ない）。`MCP_TOOLS=all` や `MCP_READONLY=0` で確認。
- **Claude が繋がらない**: URL 末尾 `/mcp` を確認。トンネルが生きているか（`/health` を tunnel URL で叩く）。`Accept: application/json, text/event-stream` を要する Streamable HTTP なので、古い SSE 専用クライアントは不可。
- **ツール呼び出しが 401**: `NENE_API_TOKEN` 失効。再ログインして再起動。
- **`Bad Request: no valid session ID`**: 正常（initialize 前の不正アクセス）。

## 関連

- ツールの使い方: [`../theming/claudedesign-mcp-guide.md`](../theming/claudedesign-mcp-guide.md)
- カタログ: [`../mcp/tools.json`](../mcp/tools.json) / 生成: `composer mcp:generate`
- ブリッジ実体: [`../../mcp-bridge/`](../../mcp-bridge/)

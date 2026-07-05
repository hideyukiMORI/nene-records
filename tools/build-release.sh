#!/usr/bin/env bash
# NeNe Records — Tier A リリース ZIP ビルドスクリプト（#707 S3-3）
#
# 使い方: bash tools/build-release.sh [version]
# 出力:   dist/nene-records-<version>.zip
#
# 設計（事前契約 2026-07-04・関所承認済み）:
# - 日常開発は NENE2 path repo（vendor/hideyukimori/nene2 は symlink）のまま。
#   配布物だけ staging で Packagist の hideyukimori/nene2 ^1.6 を解決した実体
#   vendor を組む（S2 実証済みパターン）。symlink が 1 本でも残ったら fail。
# - 共有ホスティング（docroot=public_html・Apache Alias 無し）でも静的配信できる
#   よう、Vite 出力の assets/ と theme-thumbnails/ を public_html/ 配下へ移設する。
#   PHP が FS 読みする frontend/dist/index.html と .vite/manifest.json は残す
#   （HTTP 配信は不要）。
# - 同梱は allowlist 方式（dev 専用物・秘密・巨大物の混入を構造的に防ぐ）。

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# 版は root の VERSION ファイルが単一ソース（/machine/health も同じ値を報告・#586）。
# 明示引数があればそれを優先、無ければ VERSION、それも無ければ dev。
VERSION="${1:-$(cat "$ROOT/VERSION" 2>/dev/null || echo dev)}"
DIST="$ROOT/dist"
STAGE="$DIST/build/stage"
ZIP_NAME="nene-records-${VERSION}.zip"
NENE2_CONSTRAINT="^1.6"

echo "=== NeNe Records Tier A release build: $VERSION ==="

rm -rf "$DIST/build"
mkdir -p "$STAGE"

# ----------------------------------------
# 1. フロントエンド ビルド
# ----------------------------------------
echo "→ frontend build..."
(cd "$ROOT/frontend" && npm ci --silent && npm run build)

# ----------------------------------------
# 2. アプリ本体（allowlist コピー）
# ----------------------------------------
echo "→ copying application files..."
cp -r "$ROOT/src" "$STAGE/src"
cp -r "$ROOT/database" "$STAGE/database"
cp -r "$ROOT/templates" "$STAGE/templates"
cp -r "$ROOT/public_html" "$STAGE/public_html"
mkdir -p "$STAGE/tools"
cp "$ROOT/tools/hosting-precheck.php" "$STAGE/tools/"
cp "$ROOT/tools/install.php" "$STAGE/tools/"
cp "$ROOT/tools/webhook-worker.php" "$STAGE/tools/"
cp "$ROOT/.env.example" "$STAGE/.env.example"
cp "$ROOT/phinx.php" "$STAGE/phinx.php"
cp "$ROOT/composer.json" "$STAGE/composer.json"
cp "$ROOT/README.md" "$STAGE/README.md"
# 版ファイル（/machine/health が実行時に読む単一ソース・#586）。
cp "$ROOT/VERSION" "$STAGE/VERSION"

# テーマ engine CSS: getThemeEngineCss（MCP テーマ生成）が FS 読みする唯一の
# frontend ソース（欠けても公開サイトは無傷・MCP 応答が空になるだけ）。
mkdir -p "$STAGE/frontend/src/pages/consumer"
cp "$ROOT/frontend/src/pages/consumer/public-site.css" "$STAGE/frontend/src/pages/consumer/"

# ----------------------------------------
# 3. フロント成果物 — PHP が読む 2 点は frontend/dist、HTTP 配信物は public_html へ移設
# ----------------------------------------
echo "→ placing built frontend (assets moved under public_html)..."
mkdir -p "$STAGE/frontend/dist"
cp "$ROOT/frontend/dist/index.html" "$STAGE/frontend/dist/"
cp -r "$ROOT/frontend/dist/.vite" "$STAGE/frontend/dist/"
cp -r "$ROOT/frontend/dist/assets" "$STAGE/public_html/assets"
cp -r "$ROOT/frontend/dist/theme-thumbnails" "$STAGE/public_html/theme-thumbnails"

# ----------------------------------------
# 4. var/（空・書き込み先）
# ----------------------------------------
mkdir -p "$STAGE/var"
touch "$STAGE/var/.gitkeep"

# ----------------------------------------
# 5. Packagist ^1.6 の本番 vendor（path repo / symlink を持ち込まない）
# ----------------------------------------
echo "→ composer: resolve hideyukimori/nene2 ${NENE2_CONSTRAINT} from Packagist..."
php -r '
$path = $argv[1];
$json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$json["require"]["hideyukimori/nene2"] = $argv[2];
unset($json["repositories"]);
file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
' "$STAGE/composer.json" "$NENE2_CONSTRAINT"
(cd "$STAGE" && composer update --no-dev --no-interaction --prefer-dist --no-scripts --optimize-autoloader --quiet)

NENE2_RESOLVED="$(cd "$STAGE" && composer show hideyukimori/nene2 2>/dev/null | awk '/^versions/ {print $NF}')"
echo "  nene2 resolved: ${NENE2_RESOLVED}"

# ----------------------------------------
# 6. 出荷前検査 — symlink ゼロ・nene2 実体
# ----------------------------------------
if [ "$(find "$STAGE" -type l | wc -l)" -ne 0 ]; then
    echo "✗ 配布物に symlink が混入しています:" >&2
    find "$STAGE" -type l >&2
    exit 1
fi

if [ ! -f "$STAGE/vendor/hideyukimori/nene2/src/Install/PayloadInstaller.php" ]; then
    echo "✗ vendor/hideyukimori/nene2 が実体として解決されていません。" >&2
    exit 1
fi

# ----------------------------------------
# 7. ZIP（内容物をトップレベルに = WordPress 式）
# ----------------------------------------
echo "→ zip: dist/${ZIP_NAME}..."
mkdir -p "$DIST"
rm -f "$DIST/$ZIP_NAME"
(cd "$STAGE" && zip -qr "$DIST/$ZIP_NAME" . -x "*.DS_Store")

# ----------------------------------------
# 8. サイズ実測報告（受入④）
# ----------------------------------------
RAW_SIZE="$(du -sh "$STAGE" | cut -f1)"
ZIP_SIZE="$(du -sh "$DIST/$ZIP_NAME" | cut -f1)"
FONT_SIZE="$(du -ch "$STAGE/public_html/assets/"*.woff* 2>/dev/null | tail -1 | cut -f1)"

rm -rf "$DIST/build"

echo ""
echo "✓ ビルド完了: dist/${ZIP_NAME}"
echo "  nene2:        ${NENE2_RESOLVED}"
echo "  raw:          ${RAW_SIZE}"
echo "  zip:          ${ZIP_SIZE}"
echo "  内フォント:   ${FONT_SIZE}（woff/woff2・削減は別 Issue）"

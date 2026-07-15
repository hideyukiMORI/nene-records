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
cp "$ROOT/tools/import-org.php" "$STAGE/tools/"
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
# 3b. フォントのオンデマンド分離（#709）
# ----------------------------------------
# 配布 ZIP の 8 割超は @fontsource の woff/woff2（特に CJK: Shippori Mincho /
# Noto Sans JP / Noto Sans SC で ~48MB）。共有ホスティング勢が 66MB を FTP で
# 上げるのは重いので、base zip には「設置直後に見た目が破綻しない最小セット」だけ
# を残し、残りは別アーティファクト nene-records-fonts-<version>.zip として切り出す。
# 両者は同一ビルドの成果物なので woff2 のハッシュ名（＝CSS が参照するパス）は完全
# 一致し、フォントパックを FTP で上書き展開すれば全フォントが揃う。
#
# base 同梱（keep）= 管理 UI フォント（fonts.ts の Inter/Saira/Space Grotesk/
# JetBrains）＋既定公開テーマ consumer の表示フォント Bricolage Grotesque ＋
# #818 の公開サイトフォント（Zen Kaku Gothic New subset / IBM Plex Mono）。
# それ以外（フォントピッカーの選択肢・テーマ固有フォント・CJK 大物）はパックへ。
#
# Docker/本番ビルド（docker/php/Dockerfile.prod）はこのスクリプトを通らないので
# 影響しない（全部入りのまま）。keep 一覧を変える場合は frontend 側の
# BASE_BUNDLED_FONT_FAMILIES（src/shared/lib/font-availability.ts）と揃えること。
KEEP_FONT_FAMILIES=(
    inter                          # 管理 UI body（--font-sans 既定）
    saira-semi-condensed           # 管理 UI display（--font-display 既定）
    space-grotesk                  # 管理 UI chrome（--font-chrome 既定）
    jetbrains-mono                 # 管理 UI mono（--font-mono 既定）
    bricolage-grotesque            # 既定公開テーマ consumer の display/chrome
    zen-kaku-gothic-new-jp-subset  # #818 公開サイト日本語見出し/本文（subset 同梱）
    ibm-plex-mono                  # #818 公開サイト mono ラベル/数字
)
FONTS_STAGE="$DIST/build/fonts-stage"
mkdir -p "$FONTS_STAGE/public_html/assets"
echo "→ splitting fonts (base keep-set vs on-demand pack)..."
kept_fonts=0
moved_fonts=0
shopt -s nullglob
for font in "$STAGE/public_html/assets/"*.woff "$STAGE/public_html/assets/"*.woff2; do
    base_name="$(basename "$font")"
    keep=0
    for family in "${KEEP_FONT_FAMILIES[@]}"; do
        # 末尾 '-' 境界で照合し saira-semi-condensed と saira-condensed 等の前方
        # 一致衝突を防ぐ（Vite 出力は <family>-<subset>-<weight>-<hash>.woff2）。
        case "$base_name" in
            "$family"-*)
                keep=1
                break
                ;;
        esac
    done
    if [ "$keep" -eq 1 ]; then
        kept_fonts=$((kept_fonts + 1))
    else
        mv "$font" "$FONTS_STAGE/public_html/assets/"
        moved_fonts=$((moved_fonts + 1))
    fi
done
shopt -u nullglob
echo "  fonts: base に $kept_fonts 件 / フォントパックへ $moved_fonts 件"

# OFL 表示義務のガード（#872）。Zen Kaku Gothic New subset は OFL 1.1 で、配布物は
# 著作権表示とライセンス条文を含まなければならない。subset 化で font の name テーブルは
# 空、CSS コメントは最小化で消えるため、frontend の vite.config.ts が assets/ へ emit する
# この 1 ファイルだけが表示経路。ここが欠けたまま出荷するとライセンス違反になるので、
# 静かに落ちないよう fail-loud にする（keep 判定は woff/woff2 のみを見るので通常は残る）。
OFL_NOTICE="$STAGE/public_html/assets/OFL-ZenKakuGothicNew.txt"
if [ ! -s "$OFL_NOTICE" ]; then
    echo "ERROR: $(basename "$OFL_NOTICE") が base ZIP にありません（OFL 1.1 §2 違反）。" >&2
    echo "       frontend/vite.config.ts の emitFontLicense() が生きているか確認してください（#872）。" >&2
    exit 1
fi
echo "  fonts: OFL 表示 $(basename "$OFL_NOTICE") を base に同梱 ✔"

# フォントパック導入検出用マーカー（#709）。管理画面はフォントピッカーの @font-face を
# 読み込まない（公開フォントは公開シェル/プレビュー iframe 側）ため、フォントの load
# 試行では pack 有無を判定できない。そこで docroot 直下に静的マーカーを置き、frontend は
# これを fetch して判定する（public_html/.htaccess は実在ファイルを素通しするので、
# 存在すれば Apache がそのまま JSON を返す）。base は complete:false、フォントパックが
# 同名ファイルで上書きして complete:true にする。Docker/本番/dev はこのファイルを持た
# ない（front controller が 404 を返す）ので frontend は「完備」とみなし何も表示しない。
printf '{"complete":false,"version":"%s"}\n' "$VERSION" > "$STAGE/public_html/font-pack.json"
printf '{"complete":true,"version":"%s"}\n' "$VERSION" > "$FONTS_STAGE/public_html/font-pack.json"

# フォントパックの設置手順（shell 無しの共有ホスティング前提）を同梱する。
cat > "$FONTS_STAGE/READ-ME-FIRST.txt" <<'FONTPACK_README'
NeNe Records — フォントパック / Font Pack
========================================

この ZIP は本体（nene-records-<version>.zip）から切り出した追加フォントです。
本体だけでも管理画面・既定テーマ・日本語見出しは正しく表示されますが、テーマの
フォントピッカーで選べる全フォント（Playfair Display・Oswald・Source Serif 4・
CJK の Noto Sans JP/SC・Shippori Mincho など）を使うにはこのパックが必要です。

導入方法（FTP）:
  1. 本体と同じインストール先（public_html/ がある階層）に、この ZIP の中身を
     フォルダ構成ごと **上書き** アップロードします。
  2. public_html/assets/ に woff2/woff が追加されれば完了です（本体側の同名
     ファイルとは衝突しません。ハッシュ名なので純粋に増えるだけです）。
  3. 反映確認は管理画面「外観 > カスタマイズ」のフォント選択で、追加フォントの
     「（フォントパック）」注記が消えることで分かります。

バージョンは必ず本体 ZIP と同じ <version> のフォントパックを使ってください
（ハッシュ名がビルドごとに変わるため、版がズレると反映されません）。
FONTPACK_README

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

# フォントパック（#709）— base から切り出した残りフォントを別 ZIP に。
FONTS_ZIP_NAME="nene-records-fonts-${VERSION}.zip"
echo "→ zip: dist/${FONTS_ZIP_NAME}..."
rm -f "$DIST/$FONTS_ZIP_NAME"
(cd "$FONTS_STAGE" && zip -qr "$DIST/$FONTS_ZIP_NAME" . -x "*.DS_Store")

# ----------------------------------------
# 8. サイズ実測報告（受入④）
# ----------------------------------------
RAW_SIZE="$(du -sh "$STAGE" | cut -f1)"
ZIP_SIZE="$(du -sh "$DIST/$ZIP_NAME" | cut -f1)"
FONTS_ZIP_SIZE="$(du -sh "$DIST/$FONTS_ZIP_NAME" | cut -f1)"
BASE_FONT_SIZE="$(du -ch "$STAGE/public_html/assets/"*.woff* 2>/dev/null | tail -1 | cut -f1)"

rm -rf "$DIST/build"

echo ""
echo "✓ ビルド完了"
echo "  本体:         dist/${ZIP_NAME}"
echo "  フォントパック: dist/${FONTS_ZIP_NAME}"
echo "  nene2:        ${NENE2_RESOLVED}"
echo "  raw:          ${RAW_SIZE}"
echo "  base zip:     ${ZIP_SIZE}（内フォント ${BASE_FONT_SIZE} / keep ${kept_fonts} 件）"
echo "  fonts zip:    ${FONTS_ZIP_SIZE}（オンデマンド ${moved_fonts} 件）"
echo ""
echo "  → GitHub Release には両 ZIP と各 .sha256 を添付する（docs/install-tier-a.md）。"

# directory-view（ページ階層・整理UI）ペルソナレビュー（2026-06-27）

> #651 で実装した **管理ディレクトリ表示（PR3 #654）＋ 公開パンくず/子一覧（PR2 #653）** を、ローカルに
> **パス階層 100 ページ**（`docs-demo` 型）を投入して **実機スクリーンショット＋ブラウザ操作**で、page-hierarchy 会議の
> 4 ペルソナが評価した。マイルストーン **「ページ階層/ディレクトリ 仕上げ」(#1)**。元決定は
> `page-hierarchy-personas-2026-06-27.md`、実装は PR2 #653 / PR3 #654。

## 評価材料（実機）
- **管理ディレクトリ表示**：折りたたみツリー・100 件・各行 `title ＋ Published バッジ ＋ path`・フォルダ開閉。→ 良好。
- **公開 custom-permalink ページ** `/docs/guides`：パンくず（Home › Documentation › 現在）＋ BreadcrumbList JSON-LD ＋ 「In this section」子一覧（authentication / blocks / …）。

## ★ P0 実描画検証（結果＝実バグ）
公開 custom-permalink ページは **backend SSR では正しく描画**されるが、**フロント SPA のクライアントルータが type ベース**
（`frontend/src/app/router.tsx` の `/:entityTypeSlug` / `/:entityTypeSlug/*`）で、**先頭セグメントが entity-type slug でない
任意パス**（`/company/about` 等）を解決できない。router コード＋実機（live SPA）で確認：
- **直接訪問**：SSR が描画 → ハイドレートで type 解決失敗 → 「entity type not found」に差し替わる（クローラは OK・人間で崩れる）。
- **クライアント遷移**（パンくず/子一覧の `<Link>`）：not-found。

→ PR2(#653) のパンくず/子一覧は **SSR では正しいが SPA では機能しない**。aozora は slug ベースのため未顕在化、
custom-permalink ページ作成（本デモ）で顕在化。**→ #656（P0）**。

## 判定（4 ペルソナ）
| ペルソナ | 総評 |
|---|---|
| 制作会社（チャネル）| ツリーは WP 階層より見やすい。directory で下書きフィルタ消失・最終更新日が欲しい。**親選択 UI 無し（permalink 手打ち）が最大の WP gap** |
| コーポレート運用者 | 子件数・閲覧数・更新日が欲しい。**100 件全展開は長い→初期折りたたみ**。ツリー内検索は必須 |
| 非技術ブロガー（大衆）| 触らない想定で OK（permalink は上級・既定不可視の方針どおり）。**titleless が `Record #260`** は事故っぽい |
| 移行/IA/SEO 専門家 | 公開のパンくず＋JSON-LD＋子一覧は満点。ただ **実描画(P0)** 確定が先。phantom 中間セグメントの非リンク途切れ → セクションindex 自動生成案 |

## Round 1–3（要点）
- **R1 第一印象/表示不足**：ツリーは直感的。directory モードで検索/status フィルタが消える・更新日/子件数/閲覧数が無い・初期全展開で長い。
- **R2 WP gap/アイディア**：親ページ選択が無く permalink 手打ち（→「＋ここに新規」で接頭辞補完）。手動並び順なし。**目玉＝ツリー DnD で親移動＝permalink 一括書換＋自動301**（PR1 の自動301が土台）。phantom セグメントにセクションindex 自動生成。
- **R3 改善/収束**：最優先は **公開ページの実描画(P0)**。titleless fallback を humanize に統一。directory にフィルタ復活＋表示拡充。育成案（DnD・ツリー内検索・開閉記憶）。

## findings（4 軸 × 優先度 × issue）
| 軸 | 指摘 | 優先 | issue |
|---|---|---|---|
| 実描画 | 公開 custom-permalink の SPA 解決（直接/遷移とも崩れない） | **P0** | #656 |
| 表示不足 | directory: status フィルタ復活・更新日・子件数・初期折りたたみ／titleless fallback の humanize 統一 | P1 | #657 |
| WP gap | 親選択 or permalink 接頭辞補完・手動並び順 | P1 | #658 |
| アイディア | **DnD 親移動＋自動301**・ツリー内検索・＋ここに新規 | P2 | #659 |
| 改善 | セクションindex 自動生成・パス視認性・開閉状態記憶・a11y・sitemap 階層確認 | P2 | #660 |

## 決定
- マイルストーン #1（ページ階層/ディレクトリ 仕上げ）で**端から対応**。着手順＝**P0 #656 → P1 #657（quick wins）→ #658 → P2 #659 / #660**。
- 「**DnD で親移動＝permalink 一括書換＋自動301**」が満場一致の目玉（PR1 の自動301が土台・子孫 URL 雪崩なし）。

## 関連
- 元決定：`page-hierarchy-personas-2026-06-27.md`（#651）。実装：PR2 #653 / PR3 #654。短期 TODO：`docs/todo/current.md`。

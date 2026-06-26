# 価格 / SKU ＋ 初顧客戦略（ドラフト・2026-06-26）

> **ステータス: ドラフト（要オーナー判断）。** 価格の数字は提案。最終決定は ayane が行う。
> 経営ペルソナ sequencing 会議（[`management-personas-sequencing-2026-06-26.md`](./management-personas-sequencing-2026-06-26.md)）の結論「**今＝D（コンシェルジュで初の有料客）＋B（soak）**」を実行に移すための土台。r7（[`ux-market-personas-discussion-r7-2026-06-26.md`](./ux-market-personas-discussion-r7-2026-06-26.md)）の収益モデル提案を具体化。
> 前提＝オープンコア（コアは無制限 OSS・self-host 無料）／managed SaaS が課金対象／本番で enforce 済みの課金レバーは **custom domain** のみ（records/storage/admins は定義済・近接 enforce）。課金は当面コンシェルジュ（Payment Link＋手動 plan）。

---

## PART 1 — 価格 / SKU 提案

**ポジショニング**：NeNe Records は WordPress.com（フルサイト・但しプラグイン/保守地獄）／microCMS（型付きだが headless→フロント要自作）／STUDIO・ペライチ（簡単だが非構造・非AIネイティブ）の三角の中央。楔＝*microCMS のような型付きコンテンツ ＋ WordPress.com のようなライブ公開サイト&テーマ ＋ AIネイティブ編集 ＋ ゼロ運用マネージド*。OSS コアは永久無料・無制限、課金は managed の便益に対して。本番で効く hard paywall は **custom domain**＝奇しくも JP 各社のアップグレード起点（STUDIO Mini+／WordPress Personal+／はてなPro）と同じ。

| Tier | JPY(税抜) | 独自ドメイン | 最大レコード | ストレージ | 管理ユーザー | マネージド便益 |
|---|---|---|---|---|---|---|
| **Free** | ¥0 | ✗（`slug.nene-records.com`のみ） | ~100 | 1 GB | 1 | ゼロ運用・サブドメイン自動TLS・自動更新・メール認証 |
| **Starter** | **¥1,480/月**（年¥980） | ✓ ×1 | ~1,000 | 10 GB | 2 | ＋夜間バックアップ・独自ドメインTLS・メール送信・ベストエフォート支援(メール/JST) |
| **Pro** | **¥4,980/月**（年¥3,980） | ✓ ×3 | ~10,000 | 50 GB | 10 | ＋優先/創業者サポート直通・オンデマンド画像派生・稼働監視 |
| **Enterprise** | 個別(~¥30,000/月〜) | ✓ 無制限 | 実質無制限 | 個別 | 無制限 | ＋白手袋オンボ/移行・請求書/インボイス対応・直通(JST) |

*(注: customDomain は現在 hard-enforce。records/storage/admins は定義済で近接 enforce ＝ポリシーとして売り、enforce はソフトに段階導入。)*

**ベンチマーク根拠（2026-06 時点の comp）**
- **Starter ¥1,480** … microCMS Team ¥4,900 を3×下回り、WordPress.com Personal ¥1,280／はてなPro・STUDIO Mini 帯(¥600–1,720)に整合。「独自ドメインを ~¥1,000」は市場標準で、そこに*フル構造化CMS*を載せる。
- **Pro ¥4,980** … WordPress.com Business ¥5,600 のすぐ下・Ghost Publisher(~¥4,500)/Wix Business(~¥3,800)近傍、だが microCMS Business ¥75,000 の **15分の1**＝代理店価値の見出し数字。
- **Enterprise** … microCMS/WordPress の契約・請求枠に倣う。

**なぜ払うか（tier 別）**
- *Starter*: 「VPS も Docker も PHP も触らず、独自ドメイン＋バックアップ＋TLS。microCMS より安く、API でなくライブサイトが手に入る。」
- *Pro*: 「WordPress.com Business 1枚の値段で、プラグイン地獄も保守もない代理店級の構造化CMS。」
- *Enterprise*: 「複数クライアントサイトを1基盤で・インボイス対応・作った本人に直通。」
- *vs self-host(無料OSS)*: 「self-host は永久無料・無制限＝アンチロックインの約束。払うのは*運用しない*ため。いつでもエクスポートして離脱可。」（脱出弁こそ営業文句。）

**価格リスク（solo として）**
1. **支援負荷** — ¥1,480 を手厚く支援は不可。Free/Starter は非同期・docs/AI 優先・JST 営業時間。創業者直通は Pro 以上のみ。
2. **Free 濫用/原価** — managed Free は*自分の*VPS で動く。スパム登録・ストレージ濫用・`*.nene-records.com` での phishing/SEO スパム（apex の評判汚染）。→ 既存のメール認証ゲート・低い hard cap・idle 停止・Free は独自ドメイン不可・昇格前の手動審査。
3. **JCT/インボイス** — JP 事業者（＝代理店ターゲット）に売るなら実質、適格請求書発行事業者の登録が要る（クライアントが仕入税額控除）。10% 消費税(税抜/税込表示)・申告負担・特商法表記を見込む。
4. **コンシェルジュ課金の脆さ** — 手動 plan＋Stripe リンクは proration/dunning/失敗課金なし。低volなら可、その破綻点が self-serve 化の build シグナル。

## PART 2 — 初顧客＝チャネル

**理想プロファイル**：地方 SMB（飲食・クリニック・サロン・士業・小売）向けに WordPress サイトを*作って保守する* **1〜5名の JP web制作会社/一人代理店**。10〜50 サイトを薄い運用保守マージン(¥3,000–10,000/月/件)で抱え、プラグイン更新・改ざん対応・PHP bump・速度に時間を溶かしている。乗り換え理由＝NeNe Records が保守面（プラグインなし・パッチ不要・自動TLS・壊れない構造化コンテンツ）を消しつつ、彼らの*保守収益は維持*できる。1代理店＝下流多数＋参照＝チャネル。ボーナス＝AI/Claude に明るい「一人代理店」（「AIネイティブCMS」と文化的に整合）。

**コンシェルジュ・オファー（新規ビルドゼロ）**
- **白手袋移行**：パイロットのクライアント 1〜3 件を私が WordPress から無料移行（コンテンツ＋スキーマ設計）。
- **創業者直通**：LINE/Discord で私に直結・JST・速い＝solo の不公平な強み。
- **創業メンバー価格**：Pro を生涯 ¥2,980 固定（通常 ¥4,980）。代わりにフィードバック＋公開制作実績/推薦。
- **共同マーケ**：共同事例。彼らは*自分のクライアント*へ差別化ピッチ（「壊れない・速い・AIで運用」）。代理店/ホワイトラベル tier を次に約束。
- **soak**：課金自動化の前に 1〜3 ヶ月の実運用で硬化。

**アウトリーチ（solo 実行可能）**
- *場所*：X の Web制作者界隈(#web制作)、WordPress保守の苦痛を書く note 著者、microCMS/STUDIO/Jamstack・もくもく会 Discord、ランサーズ/ココナラ web制作プロフィール、X で WP のハック/保守に*愚痴る人*（温かい inbound）。
- *3文ピッチ(JP)*：「WordPressの保守（更新・改ざん対応・表示速度）に時間を取られていませんか？ NeNe Recordsは、プラグイン地獄のない“壊れにくい”AIネイティブCMSで、独自ドメイン・自動TLS・バックアップ込みのマネージド運用です。創業メンバーとして、クライアントサイト1件を私が無料で移行し、特別価格でお使いいただけませんか？」
- *最初の ask（低摩擦）*：「買って」でなく「30分カジュアル面談」or「1サイトだけ無料移行させてください」。＝パイロットであって契約ではない。

**効いているシグナル → 次を build**
- *実際の有料クライアント*サイトが公開され、**soak 後も維持**（新規性でなく retention）。
- **2件目/3件目を自発的に**持ち込む → チャネル仮説 validated。
- **初の実 JPY が Stripe Payment Link で着金して定着**（返金/churn なし）。
- 事例から **独自ドメインを求める inbound が ≥3** → 有料レバーの需要。
- 手動 plan/請求追いが週数時間を超えたら **self-serve Stripe checkout** を build。
- 非技術 inbound が「さくら/ロリポップ/エックスサーバーに入れられる？」を繰り返したら **zip インストーラ**（mass entry・HETEML PHP8.4 gate＝`tools/hosting-precheck.php`）。

---

*Comp 検証 2026-06: microCMS / STUDIO / WordPress.com / ペライチ / Ghost(Pro) / はてなブログPro / Wix JP。数字は提案であり要オーナー確定。*

# Current Work

Last updated: 2026-05-24

## Up Next（Phase 7 / CMS Mid-Term M1）

| 優先 | 項目 | 備考 |
| --- | --- | --- |
| P0 | #78 Consumer Markdown マージ | body + footer Markdown レンダリング |
| P0 | Users + Admin ログイン + API auth | 書込 endpoint 保護 |
| P0 | Publish status + `published_at` | draft / published、公開フィルタ |
| P1 | Public slug routing | `/view/{type}/{slug}` |
| P1 | Admin Markdown エディタ UX | preview 付き |

詳細ギャップ表・3 フェーズ計画: `docs/milestones/2026-06-cms-mid-term.md`（#82）

## In Progress

| Issue | Summary |
| --- | --- |
| — | （なし — #80 マージ済み、ここから M1 起票） |

## Recently Completed

| Issue | Summary |
| --- | --- |
| #80 | Site Settings（defs + values + revisions）+ Consumer 反映 (PR #81) |
| #72 | Consumer Views Phase 6 強化 (PR #73) |
| #69 | Analytics / access log API (PR #70) |
| #67 | 全 API MCP カタログ 59 tools (PR #68) |
| #65 | MCP relation tools カタログ (PR #66) |
| #63 | Consumer view relation リンク表示 (PR #64) |
| #75 | Public bootstrap API + HTML + React seed (PR #76) |

## Phase 3–6 サマリー（完了）

- **Tags / Relations / Query:** API + Admin UI 一式
- **Phase 5:** MCP カタログ、Analytics（detachEntityRelation MCP は upstream 待ち）
- **Phase 6:** Consumer `/view`、public record bootstrap、site settings

## CMS 中期 TODO（Issue 未起票 → M1 から順に起票）

- [ ] Users テーブル + session/JWT + AuthGate
- [ ] Mutating API auth middleware（public GET は open）
- [ ] Publish workflow（status enum + published_at + Admin UI）
- [ ] #78 マージ後: footer Markdown を PublicShell でもレンダリング
- [ ] Public slug（field convention + route + bootstrap）
- [ ] Per-record SEO（meta override on bootstrap）
- [ ] Entity record revisions（settings パターン）
- [ ] Image/file field + upload API + media UI
- [ ] Navigation / menu settings
- [ ] Full-text search API
- [ ] Scheduled publish / webhooks / import-export（M3）

## Verification

```bash
composer check                    # 165 tests + openapi + mcp
npm run check --prefix frontend   # 68 tests
docker compose exec app vendor/bin/phinx migrate
```

# Frontend Self-Review

Use for admin or consumer React/TypeScript changes.

Source policies:

- `docs/development/coding-standards.md`
- NENE2: `docs/development/frontend-integration.md`

## Checklist

- [ ] API calls go through typed fetch wrappers, not ad-hoc URLs scattered in components.
- [ ] Admin UI does not bypass API validation assumptions (schema edits still POST to API).
- [ ] No secrets or API keys embedded in frontend source.
- [ ] Build output goes to `public_html/assets/` when integrated; `node_modules/` is not committed.
- [ ] `npm run check --prefix frontend` passes when frontend exists.
- [ ] PR notes mention this checklist when frontend-facing.

# NeNe Records — Admin Frontend

React + TypeScript admin SPA for NeNe Records.

Policy: [`docs/development/frontend-standards.md`](../docs/development/frontend-standards.md)

## Commands

```bash
npm install
npm run dev          # http://localhost:5173 (API proxied to :8080)
npm run storybook    # component catalog on :6006
npm run check        # type-check, lint, format, test, build-storybook
```

## Structure

- `src/app/` — providers, router, auth shell
- `src/pages/` — route wiring (`pages/consumer/` = public `/view/*` shell)
- `src/features/` — user workflows (`public-browse-*`, `public-view-*` = consumer-only)
- `src/entities/` — API resource slices (TanStack Query)
- `src/shared/ui/` — theme tokens, primitives, Storybook catalog

Public consumer routes: `/view` (type index), `/view/:slug` (list), `/view/:slug/:id` (detail).

Theme swap: edit `src/shared/ui/theme/active.css` import only. Consumer shell uses `themes/consumer-brand.css` via `pages/consumer/consumer-theme.css` (`data-theme="consumer"`).

API-only consumer patterns: [`docs/examples/consumer-api.md`](../docs/examples/consumer-api.md).

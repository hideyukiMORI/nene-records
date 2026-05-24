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
- `src/pages/` — route wiring
- `src/features/` — user workflows
- `src/entities/` — API resource slices (TanStack Query)
- `src/shared/ui/` — theme tokens, primitives, Storybook catalog

Theme swap: edit `src/shared/ui/theme/active.css` import only.

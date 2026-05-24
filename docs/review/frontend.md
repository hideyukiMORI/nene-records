# Frontend Self-Review

Use for admin or consumer React/TypeScript changes (Phase 4+). Policy source: **`docs/development/frontend-standards.md`**.

Also read:

- `docs/development/coding-standards.md`
- NENE2: `docs/development/frontend-integration.md`

## Architecture and dependencies

- [ ] Layer imports follow `app → pages → features → entities → shared` only (no upward or cross-feature imports).
- [ ] Entities and features imported only via their `index.ts` public surface.
- [ ] ESLint boundary / `import/no-restricted-paths` passes — violations are merge blockers.
- [ ] No sibling `entities/*` imports; orchestration lives in `features/`.

## Placement (zero tolerance)

- [ ] Entity folders match canonical tree (`ids`, `model`, `mapper`, `query-keys`, `queries`, `mutations`, `index.ts`; `enum` / `api-types` when needed).
- [ ] No models, enums, DTOs, TanStack hooks, or MSW handlers outside mandated paths.
- [ ] No `shared/api/generated/` or `api-types` imports from `features/`, `pages/`, or `*.tsx` (except colocated `*Props`).
- [ ] No root `src/types/` or ad-hoc type dumps in `utils.ts` / `helpers.ts`.
- [ ] Generated OpenAPI types only in `shared/api/generated/`; not hand-edited.

## Data flow

- [ ] Read path: API → client → mapper → entity query → feature hook → UI (models only in UI).
- [ ] Write path: UI → mutation hook → client → API; explicit query invalidation on success.
- [ ] No `useEffect` + `fetch` for server data; no API responses duplicated in `useState`.
- [ ] Screens implement loading, empty, error, and success states explicitly.
- [ ] Optimistic updates (if any) roll back on Problem Details failure and have a test.

## Design system and theming

- [ ] All visual tokens live in `shared/ui/theme/themes/*.css`; `active.css` is the only swap point for full theme change.
- [ ] No hex/rgb/hsl/px literals or Tailwind arbitrary values (`[…]`) in components, features, or pages.
- [ ] Components use semantic Tailwind utilities only (`bg-surface`, `text-primary`, `p-inline-md`, etc.).
- [ ] Features import UI only from `shared/ui` barrel — no deep `primitives/` paths, no styled raw HTML elements.
- [ ] Optional `tokens.ts` mirrors CSS vars — not a second source of truth.

## Storybook and component contracts

- [ ] Every exported `shared/ui` primitive and composed component has a colocated `*.stories.tsx`.
- [ ] Story file header documents **In**, **Out**, and **Does not** boundaries.
- [ ] Stories cover default, disabled (if applicable), and all variants; no API/Query/router in primitive stories.
- [ ] `npm run build-storybook --prefix frontend` passes (included in `check`).

## Design patterns

- [ ] Feature split: `hooks/` (logic) + `ui/` (presentational); no fetch/query in `shared/ui`.
- [ ] Query keys only from `entities/{resource}/query-keys.ts` — no string literals in features.
- [ ] Forms use React Hook Form + Zod (UX); server errors mapped from Problem Details.
- [ ] Destructive actions use confirm dialog from `shared/ui`.
- [ ] Named exports only; no class components or default exports.

## TypeScript

- [ ] No `any`, unjustified `@ts-ignore`, or `!` without invariant comment.
- [ ] Branded IDs in `entities/{resource}/ids.ts`.
- [ ] Env vars read only through `shared/config/env.ts`.

## API and security

- [ ] HTTP only in `shared/api/client.ts`; Problem Details parsed in `shared/api/errors.ts`.
- [ ] Schema writes go to API; UI does not treat client validation as authoritative.
- [ ] No secrets or tokens in source; auth storage matches API policy.
- [ ] 401/403 fail closed; no silent unauthenticated writes.
- [ ] No `dangerouslySetInnerHTML` without sanitization policy and Issue reference.

## Testing

- [ ] Entity: mapper tests (+ query-key tests if non-trivial); hook tests with MSW for primary query/mutation.
- [ ] Feature: component tests — happy path + primary error path.
- [ ] MSW handlers match OpenAPI; live in `tests/msw/` or mandated `__msw__/` path.
- [ ] Factories build **models**, not raw DTOs, in `tests/factories/`.
- [ ] Tests use role/label/text queries and `userEvent`; no shallow child mocks.
- [ ] Bug fixes include regression test unless Issue waives.

## Build, a11y, CI

- [ ] `npm run check --prefix frontend` passes (type-check, lint, format, test).
- [ ] Keyboard accessible primary flows; controls have accessible names.
- [ ] `node_modules/` and generated assets not committed; lockfile updated if deps changed.

## PR

- [ ] PR body includes `Self-review: frontend` and verification commands run.
- [ ] Reviewer rejects merge on placement, dependency, data-flow, or security violations — no deferrals.

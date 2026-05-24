# Agent / AI Guide

This file is the entry point for AI agents working on NeNe Records.

## Read First

- **Current work & status:** `docs/todo/current.md` — M1 完了、M2 Up Next
- **Product vision:** `docs/explanation/product-vision.md`
- Inheritance map: `docs/inheritance-from-nene2.md`
- Human and AI collaboration: `docs/CONTRIBUTING.md`
- Workflow: `docs/workflow.md`
- Coding standards: `docs/development/coding-standards.md`
- Backend standards: `docs/development/backend-standards.md`
- Frontend standards (Phase 4+; policy now): `docs/development/frontend-standards.md`
- Commit messages: `docs/development/commit-conventions.md`
- AI tool policy: `docs/integrations/ai-tools.md`
- Roadmap: `docs/roadmap.md`
- Milestones: `docs/milestones/2026-06-cms-mid-term.md`

## Operating Rules

- Work from GitHub Issues. Create an Issue before implementation or doc changes that lack one.
- Do not commit directly to `main`. Use branches named like `type/issue-number-summary`.
- Read NENE2 upstream docs for framework behavior; read local docs for product rules.
- Keep `docs/todo/current.md` and milestones aligned with Issues and PRs.
- Keep changes focused. Do not mix governance, feature work, and unrelated cleanup in one PR.
- Do not commit secrets, credentials, local `.env` files, or generated build outputs.
- Prefer explicit, typed, testable code over hidden framework behavior.
- When docs and Cursor rules conflict, update the docs first and keep `.cursor/rules/` concise.

## Project Direction

NeNe Records is an API-first flexible entity platform on NENE2:

- JSON APIs first, with OpenAPI as the contract.
- Typed entity fields and schema registry — not WordPress-style untyped meta.
- Admin frontend owns most configuration UX; API enforces validation and auth.
- MCP tools expose the same API boundary to AI clients.
- Consumer views are replaceable presentation layers.

## Framework Reference

Install `hideyukimori/nene2` via Composer. For HTTP runtime, middleware, Problem Details, and MCP patterns, NENE2 upstream documentation is authoritative unless a local ADR says otherwise.

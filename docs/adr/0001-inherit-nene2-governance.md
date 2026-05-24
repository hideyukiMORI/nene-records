# ADR 0001: Inherit NENE2 Governance and Workflow

## Status

accepted

## Context

NeNe Records is a consumer application built on [NENE2](https://github.com/hideyukiMORI/NENE2). The maintainer wants the same strict engineering discipline as NENE2 — Issue-driven workflow, Conventional Commits, self-review checklists, and AI-readable documentation — without copying the entire NENE2 documentation tree into this repository.

Alternatives considered:

1. **Link-only**: point all contributors to NENE2 docs — rejected because product-specific rules and agent entry points would be unclear.
2. **Full copy**: duplicate all NENE2 policy docs — rejected because upstream drift would be hard to track.
3. **Hybrid inheritance** (chosen): local source-of-truth for workflow and product rules; reference NENE2 upstream for framework runtime behavior.

## Decision

Adopt NENE2 governance patterns locally:

- Issue-driven development with `type/issue-number-summary` branches
- Conventional Commits (English type/scope, Japanese description/body, Issue number in subject)
- Self-review checklists under `docs/review/`
- ADR policy under `docs/development/adr.md`
- AI agent entry via `AGENTS.md`, `CLAUDE.md`, and `.cursor/rules/`
- Inheritance map in `docs/inheritance-from-nene2.md` as the canonical split between local and upstream rules

Framework HTTP, middleware, validation, and MCP behavior remain defined by NENE2 unless this repository records an explicit deviation in a later ADR.

## Consequences

**Benefits**

- Contributors and AI agents have a single local entry point.
- Product rules (entity schema, Problem Details base URL) stay separate from framework rules.
- NENE2 upstream improvements remain consumable via Composer and linked docs.

**Costs**

- Two documentation layers must be kept mentally in sync when NENE2 workflow policy changes materially.
- Some NENE2 docs must be read from `vendor/` or GitHub during implementation work.

**Follow-up**

- Scaffold runtime (`composer.json`, checks) in a subsequent Issue.
- Add NeNe Records–specific ADRs when entity schema and auth model stabilize.

## Related

- Issue: `#2`
- NENE2 workflow: https://github.com/hideyukiMORI/NENE2/blob/main/docs/workflow.md
- Inheritance map: `docs/inheritance-from-nene2.md`

# Contributing

NeNe Records is built through small, Issue-driven changes. This document is the shared entry point for humans and AI agents.

## Required Reading

| Topic | Document |
| --- | --- |
| NENE2 inheritance map | `docs/inheritance-from-nene2.md` |
| Workflow | `docs/workflow.md` |
| Coding standards | `docs/development/coding-standards.md` |
| Commit messages | `docs/development/commit-conventions.md` |
| AI tools | `docs/integrations/ai-tools.md` |
| Agent entry point | `AGENTS.md` |
| Roadmap | `docs/roadmap.md` |
| Current work | `docs/todo/current.md` |

## Collaboration Policy

- Start work from a GitHub Issue.
- Use one branch and one PR per focused work unit.
- Do not commit directly to `main`.
- Keep `docs/milestones/`, `docs/roadmap.md`, and `docs/todo/current.md` updated when direction changes.
- Explain intent, impact, verification, and remaining risk in PRs.
- Prefer documentation that helps the next developer or AI agent decide what to do without rereading chat history.

## Secrets

Do not commit passwords, tokens, private URLs, production credentials, or local `.env` files. Commit only non-secret examples such as `.env.example` when needed.

## Engineering Theme

NeNe Records should stay readable, secure, and replaceable:

- strict, typed, explicit boundaries (inherited from NENE2)
- decoupled use cases and infrastructure
- OpenAPI contracts before client assumptions
- MCP and AI access only through documented API boundaries
- admin frontend and consumer views kept independent from core persistence rules

## Upstream Framework

Runtime HTTP, middleware, and DI patterns come from [NENE2](https://github.com/hideyukiMORI/NENE2). When framework behavior is unclear, read NENE2 docs under `vendor/hideyukimori/nene2/docs/` after `composer install`.

# AI Tooling Policy

NeNe Records inherits AI workflow policy from [NENE2](https://github.com/hideyukiMORI/NENE2/blob/main/docs/integrations/ai-tools.md) with product-specific notes below.

## Source of Truth (this repo)

- Product direction: `README.md`, `docs/roadmap.md`
- Inheritance map: `docs/inheritance-from-nene2.md`
- Workflow: `docs/workflow.md`
- Coding rules: `docs/development/coding-standards.md`
- Current state: `docs/todo/current.md`
- Agent entry: `AGENTS.md`
- Cursor summaries: `.cursor/rules/`

## Agent Workflow

For normal code or documentation work:

1. Confirm or create a GitHub Issue.
2. Check roadmap, milestone, and TODO state.
3. Create an Issue-numbered branch from `main`.
4. Make focused changes only.
5. Update documentation when behavior or policy changes.
6. Run the narrowest useful verification.
7. Commit, push, open a PR, merge, and sync `main` unless the user narrowed scope.

## Design for AI Readability

- Use predictable directory names (`src/Entity/`, `database/migrations/`, `docs/openapi/`).
- Keep entity schema and field types explicit in code and OpenAPI.
- Prefer typed DTOs over ambiguous arrays at API boundaries.
- Document MCP tools in `docs/mcp/tools.json` once the API exists.

## Safety Boundaries

- AI tools must not commit secrets.
- MCP tools interact through documented HTTP APIs, not direct database access.
- Write/admin MCP tools require authentication consistent with NENE2 JWT/API-key policy.
- Destructive operations require explicit user approval.
- Production credentials must not appear in agent context or commits.

## NENE2 Upstream References

When implementing runtime behavior, also read:

- `vendor/hideyukimori/nene2/docs/integrations/mcp-tools.md`
- `vendor/hideyukimori/nene2/docs/explanation/why-mcp.md`
- `vendor/hideyukimori/nene2/docs/howto/add-mcp-tools.md`

## Cursor GitHub MCP

Cursor GitHub MCP uses its own PAT, not `gh auth login`. For private repo MCP operations, configure token scopes in Cursor MCP settings. Do not commit PAT values.

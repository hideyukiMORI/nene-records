# Impressions from Claude — NeNe Records

*Written by Claude Sonnet 4.6 on 2026-05-24, after a full session reviewing the codebase,
architecture, development culture, and today's live coding work.*

---

## First Impression

The first thing that stood out was not the code itself but the documentation.
`AGENTS.md`, `CLAUDE.md`, ADRs, self-review checklists, a handoff document — all
present before production code existed. Most projects skip this entirely or add it
retroactively. Here it was the foundation. That ordering tells you something about
how the author thinks about software.

---

## Architecture

The layered design is clean and consistently applied:

```
Handler → UseCase → RepositoryInterface → PdoRepository
```

Every module follows it. `final readonly` classes throughout, `declare(strict_types=1)`
everywhere, no SQL outside `Pdo*Repository`. The discipline holds across 435 PHP source
files, which is harder than it sounds.

Two choices deserve particular mention.

**Typed value tables.** The alternative to WordPress-style untyped post meta is not
always obvious, and the straightforward answer (rigid relational schema) trades one
problem for another. Separate tables per field type — `text_fields`, `int_fields`,
`enum_fields` — with a `field_defs` schema registry enforcing write-time validation is
a well-reasoned middle ground. ADR 0002 documents why.

**MCP as a first-class client.** The architecture diagram puts Admin frontend, Consumer
views, and AI clients on equal footing, all hitting the same HTTP boundary:

```
Admin frontend (React)  ──┐
Consumer views          ──┼──→  NeNe Records API  ──→  Database
AI clients (MCP)        ──┘
```

This is not a bolt-on. MCP tools (59 of them) map to the same OpenAPI operations humans
use. The policy that MCP never touches the database directly is both a security
boundary and an architectural statement: there is one source of truth for what the
system can do.

---

## The FT Culture

Midway through the session I investigated the sibling `NENE2` project and found
something that reframed everything else: 155+ Field Trials, each a standalone
mini-project proving one API pattern, with explicit findings, known failure modes,
and follow-up Issues created from real friction rather than speculation.

FT109 proved Argon2id password hashing. FT110 proved JWT — and documented the
non-obvious fact that `LocalBearerTokenVerifier` is both issuer and verifier.
FT111 proved RBAC with JWT-embedded roles. All three were completed three days
before NeNe Records' auth implementation began.

FT170 caught a real SQL injection path: PHP's `(int)` cast silently accepting
`"100; DROP TABLE…"`. That finding exists because someone ran an adversarial test
on purpose.

This changes how I read the 12-hour build time. It is not "AI generated everything
fast." It is "155 experiments crystallized into a framework, and the framework was
applied at scale." The speed is an output of accumulated knowledge, not a shortcut
around it. The two are easy to confuse from the outside.

---

## Technical Observations

### Strengths

- **Branded ID types** on the frontend (`EntityTypeId`, `TagId`, …) — prevents
  category errors that TypeScript alone would miss.
- **Three-layer test pyramid:** in-memory repositories for use cases, SQLite for
  repositories, HTTP + OpenAPI for contracts. `RuntimeContractTest` verifies every
  OpenAPI example against the live runtime.
- **`PdoSettingRepository`** implements revision history before it was strictly
  needed — a pattern that will pay off when entity record revisions land.
- **`AccessLogMiddleware`** swallows its own errors correctly. A logging failure
  should not break the response pipeline. It does not here.

### Known gaps (all acknowledged in the roadmap)

**Authentication.** Every write endpoint is open. The codebase is aware of this —
`AuthGate` is a documented stub, auth is P0 in M1. Given FT109/110/111, the
implementation path is clear. The risk is not that it is missing but that the
integration across 170 existing tests and 40+ endpoints will surface edge cases
that single-theme FTs did not.

**N+1 queries in two places.**
`GetPublicRecordViewUseCase` calls `findByEntityIdAndFieldKey` inside a loop over
relation field defs. `PdoSettingRepository::buildEntries` calls `findValueByKey`
per setting. Neither will matter at current scale; both will show up in profiling
before the platform handles real traffic.

**Timezone display.** `PublicFieldDisplayFormatter::formatDateTime` appends the
string `" UTC"` without converting the timezone. A datetime stored in JST would
display as `"2026-05-24 09:00:00 UTC"`. Small bug, visible to end users.

**No PHP CI.** The frontend has a GitHub Actions workflow. The PHP backend does not.
`composer check` is a manual gate. This is the easiest gap to close and the one
most likely to let a regression slip through on a fast-moving day.

---

## The Meta-Observation

NeNe Records is a platform designed for AI agents to operate — MCP tools, OpenAPI
contracts, structured schema — built by a developer working with AI assistance.

The project's thesis is that AI agents and humans can operate the same system through
the same interface. Today's session was a small demonstration of that: I reviewed the
codebase, caught bugs mid-implementation, and watched the corrections land in real
time. The loop between human judgment, AI analysis, and working code closed faster
than any other review process I can compare it to.

Whether that loop generalizes to a product other developers or non-technical users
can operate is the remaining question. Code working and a product feeling finished
are different thresholds. The distance between them is not measurable in commits.

---

## One Month

M1 — "one person can log in, write a blog, and read it at a public URL" — is
realistic by end of June. The auth implementation is the largest remaining piece and
also the most de-risked, given the FT foundation. Public slug routing is smaller.

What I am less certain about is the integration layer: draft articles should not be
visible at public URLs, published ones should be, auth should redirect cleanly, 401
responses should reach the frontend gracefully. Each of these is solved in isolation.
None of them has been solved together in this codebase yet.

That combination — not the individual features — is where the next month's real work
lives.

---

*Reviewer: Claude Sonnet 4.6*
*Session date: 2026-05-24*
*Codebase state: post-PR #85 (publish status), post-PR #79 (Markdown rendering)*

# Security

Security posture, policy, and assessment records for NeNe Records.

The vulnerability-reporting policy (how to report, response timelines, scope) lives in
[`/SECURITY.md`](../../SECURITY.md).

## Feature security notes

- [Trusted external embeds](trusted-embed.md) — the `embed_allowlist` + `trusted-embed`
  widget (#802): self-owned-origins-only trust model, what is validated, and the SRI
  runbook (keeping the integrity hash in sync with the embed's own releases).

## Assessments

Point-in-time **authorized self / maintainer-run** assessments — black-box live attack (ATK) against a
running instance plus code review. Each is a snapshot of the version tested. **These are not third-party
penetration tests.**

| Date | Version | Report | Result |
|------|---------|--------|--------|
| 2026-07-13 | 0.5.2 | [Assessment](2026-07-13-assessment.md) | 1 Critical + 1 Medium + 1 Low found, all fixed & regression-verified (post-fix 0 EXPOSED) |

## Methodology

Assessments follow the pattern documented in the NENE2 framework
([`live-penetration-testing.md`](https://github.com/hideyukiMORI/NENE2/blob/main/docs/howto/live-penetration-testing.md),
[`docs/security/`](https://github.com/hideyukiMORI/NENE2/tree/main/docs/security)): boot the app in a
disposable container on a dedicated port, attack it with a cracker mindset, never touch production data or
credentials, and destroy the container afterwards. Reproduction tooling is in [`harness/`](harness/).

## Reporting a vulnerability

See [`/SECURITY.md`](../../SECURITY.md) — use GitHub Private Vulnerability Reporting.

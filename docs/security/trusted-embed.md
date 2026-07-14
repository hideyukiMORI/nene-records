# Trusted external embeds (`embed_allowlist` + `trusted-embed` widget)

How to safely put a **self-owned external script** on a public page (#802), and the
operational rules that keep it safe. Introduced across two phases:

- **Phase 1** — the per-org `embed_allowlist` setting widens the public-page CSP
  (`script-src` / `connect-src` / `frame-src`) by the listed origins only.
- **Phase 2** — the first-party `trusted-embed` widget emits the actual
  `<script src integrity crossorigin="anonymous" async>`, gated by the allowlist,
  on both the SSR shell and the SPA.

The content sanitizer is **never** relaxed: user-authored HTML still cannot carry a
`<script>`. Embeds only come from this typed, admin-vetted path.

## Trust model: self-owned origins only

The allowlist exists for **origins you own and operate** — typically another of your
own subdomains (e.g. `https://widgets.example.com` hosting your own form/booking
widget). Such an origin is, in practice, the same operator, so loading its script on
your public page is the same trust decision as shipping your own first-party code.

**Do not** add third-party origins you do not control (ad networks, arbitrary SaaS
embed snippets, "paste this script" widgets). A script from an allowlisted origin runs
in your public page's origin context; a third party you allowlist can therefore change
what runs on your site at any time, with no further review. Third-party embeds belong
in a different, sandboxed trust tier (not this mechanism). Only `admin` / `superadmin`
can edit the allowlist, and it is intentionally capped and https-only (no wildcards).

Because the public session cookie is `HttpOnly` and public pages carry no sensitive
same-origin data, the blast radius of a *self-owned* embed is limited — but the origin
is still trusted on the admin's word, so keep the list short and self-owned.

## What is validated

Both the write path (widget save) and the read path (SSR + SPA render) enforce the
same rules; the read path re-checks independently (defense in depth), and the CSP is a
third, separate layer.

| Field        | Rule |
|--------------|------|
| `origin`     | `https://` + explicit host (+ optional port). No wildcard, no path. **Must be on `embed_allowlist`.** |
| `src`        | Absolute `https://` URL whose origin **exactly equals** `origin`. A cross-origin `src` is refused. |
| `integrity`  | **Required.** One or more SRI hashes: `sha256-…` / `sha384-…` / `sha512-…` (base64). |
| `attributes` | Optional. `data-*` keys only, string values (HTML-escaped on output). No event handlers / arbitrary attributes. |

Any failure ⇒ the embed is **not rendered** (and, on save, the admin gets a
field-level error). A misconfiguration fails closed.

## SRI runbook — keep the integrity hash in sync with the script

Subresource Integrity pins the embed to an exact file. **If the self-owned script
changes, its hash changes, and the browser will refuse to load it until the widget's
`integrity` is updated.** This is the intended safety property, but it means a coupled
release step whenever you ship a new version of your own embed:

1. **Version the embed URL** where possible (`/widget/v3/form.js`, or a content-hashed
   filename). A stable URL + changing bytes is the classic "silently broken embed"
   trap — the old `integrity` no longer matches and the widget disappears.
2. **When you deploy a new embed build**, regenerate the SRI hash of the served file:
   ```bash
   curl -fsS https://widgets.example.com/form.js \
     | openssl dgst -sha384 -binary \
     | openssl base64 -A \
     | sed 's/^/sha384-/'
   ```
3. **Update the `trusted-embed` widget's `integrity`** to the new value (and the `src`
   if the URL changed) **before or together with** the embed deploy. To avoid a gap you
   can list **both** the old and new hashes in `integrity` (space-separated) during the
   rollover — the browser accepts the resource if it matches *any* listed hash.
4. **Verify** the public page: the script must load with **no** `Failed to find a valid
   digest … integrity` error in the browser console.

Treat the embed's own deploy pipeline and this widget's `integrity` as **linked
releases**. A CDN that transparently minifies/re-compresses responses can also change
the bytes (and thus the hash) — pin/disable such transforms for the embed path.

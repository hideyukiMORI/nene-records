<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * The per-org allowlist of trusted external origins that a public page may embed
 * (e.g. a first-party form widget hosted on one of the operator's own
 * subdomains). Sourced from the org's `embed_allowlist` public setting and
 * consumed by {@see PublicHtmlCsp::build()} to widen `script-src` / `connect-src`
 * / `frame-src` — and *only* those — by the exact origins listed.
 *
 * Trust model (see #802 / tiered-trust): the allowlist is admin-configured and
 * MUST only contain **self-owned, self-operated origins** (the operator's own
 * subdomains), never arbitrary third-party hosts. The relaxation is safe because
 * such an origin is effectively the same operator; this mechanism is not a
 * general third-party-JS host. Third-party embeds require a different, sandboxed
 * trust tier — do not repurpose this one.
 *
 * The read side here is the security boundary: every origin is re-validated and
 * anything malformed is dropped, so the CSP only ever receives well-formed
 * `https://host[:port]` origins (no scheme other than https, no wildcard, no
 * path/query/fragment/userinfo).
 */
final readonly class EmbedAllowlist
{
    /** Defensive cap so a misconfigured setting can't bloat the header. */
    private const MAX_ORIGINS = 10;

    /** `https://` + dotted host (labels a-z0-9, hyphen-internal) + optional port. */
    private const ORIGIN_PATTERN =
        '#^https://[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(:\d{1,5})?$#';

    /** @param list<string> $origins already-validated `https://host[:port]` origins */
    private function __construct(private array $origins)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Build from a flat `settingKey => effectiveValue` map (public settings).
     * The `embed_allowlist` value is a JSON array of origin strings. Invalid or
     * duplicate entries are silently dropped (the write path / admin UI is where
     * bad input is surfaced; the read path stays fail-safe).
     *
     * @param array<string, string> $settings
     */
    public static function fromSettings(array $settings): self
    {
        $raw = trim($settings['embed_allowlist'] ?? '');
        if ($raw === '') {
            return self::empty();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return self::empty();
        }

        $origins = [];
        foreach ($decoded as $entry) {
            if (!is_string($entry)) {
                continue;
            }
            $origin = strtolower(trim($entry));
            if (preg_match(self::ORIGIN_PATTERN, $origin) !== 1) {
                continue;
            }
            if (!in_array($origin, $origins, true)) {
                $origins[] = $origin;
            }
            if (count($origins) >= self::MAX_ORIGINS) {
                break;
            }
        }

        return new self($origins);
    }

    public function isEmpty(): bool
    {
        return $this->origins === [];
    }

    /** @return list<string> validated `https://host[:port]` origins */
    public function origins(): array
    {
        return $this->origins;
    }
}

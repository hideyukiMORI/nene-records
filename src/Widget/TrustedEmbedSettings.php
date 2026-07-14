<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Validation\ValidationError;

/**
 * The typed settings shape of a `trusted-embed` widget (#802): a single external
 * `<script>` loaded from a self-owned, admin-vetted origin with Subresource
 * Integrity (SRI).
 *
 * ```
 * {
 *   "origin":    "https://widgets.example.com",
 *   "src":       "https://widgets.example.com/form.js",
 *   "integrity": "sha384-Base64==",
 *   "attributes": { "data-form-id": "abc123" }   // optional, data-* only
 * }
 * ```
 *
 * Two entry points, sharing one rule set:
 *
 * - {@see validate()} is the **write path** (widget create/update): it returns
 *   field-level {@see ValidationError}s so the admin sees exactly what is wrong.
 * - {@see tryParse()} is the **read path** (public rendering / SSR + the CSP
 *   allowlist check): it returns a fully-formed value object, or `null` if the
 *   stored settings are malformed. The read path never trusts the write path —
 *   this is defense in depth, so a row that somehow bypassed validation still
 *   cannot emit an unvalidated script.
 *
 * Rules (identical on both paths):
 * - `origin`: `https://` + explicit dotted host (+ optional port). No wildcard,
 *   no path/query/fragment/userinfo, no scheme other than https.
 * - `src`: an https URL whose **origin exactly equals** `origin` (scheme + host
 *   + port). A cross-origin `src` is rejected — the embed can only load from the
 *   origin the admin vetted.
 * - `integrity`: one or more SRI hashes of the form `sha256|sha384|sha512-<b64>`.
 * - `attributes`: optional map; keys must be `data-*` (lowercased, no event
 *   handlers / `src` / arbitrary attributes), values must be strings.
 */
final readonly class TrustedEmbedSettings
{
    /** `https://` + dotted host (labels a-z0-9, hyphen-internal) + optional port; nothing after the authority. */
    private const ORIGIN_PATTERN =
        '#^https://[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(:\d{1,5})?$#';

    /** One or more space-separated SRI hashes: `sha(256|384|512)-<base64>`. */
    private const INTEGRITY_PATTERN =
        '#^(sha(256|384|512)-[A-Za-z0-9+/]+={0,2})( sha(256|384|512)-[A-Za-z0-9+/]+={0,2})*$#';

    /** `data-` + at least one char, lowercase letters/digits/hyphen only. */
    private const DATA_ATTRIBUTE_PATTERN = '#^data-[a-z0-9-]+$#';

    /** Defensive cap so a single widget cannot carry an unbounded attribute bag. */
    private const MAX_ATTRIBUTES = 20;

    /** @param array<string, string> $attributes validated `data-*` => string map */
    private function __construct(
        public string $origin,
        public string $src,
        public string $integrity,
        public array $attributes,
    ) {
    }

    /**
     * Write-path validation. Returns a (possibly empty) list of field errors,
     * scoped under `settings.*` to match the request body shape.
     *
     * @param array<string, mixed> $settings
     * @return list<ValidationError>
     */
    public static function validate(array $settings): array
    {
        $errors = [];

        $origin = is_string($settings['origin'] ?? null) ? strtolower(trim($settings['origin'])) : '';
        $src = is_string($settings['src'] ?? null) ? trim($settings['src']) : '';
        $integrity = is_string($settings['integrity'] ?? null) ? trim($settings['integrity']) : '';

        if ($origin === '') {
            $errors[] = new ValidationError('settings.origin', 'Embed origin is required.', 'required');
        } elseif (preg_match(self::ORIGIN_PATTERN, $origin) !== 1) {
            $errors[] = new ValidationError('settings.origin', 'Embed origin must be an explicit https origin (no wildcard, no path).', 'invalid');
        }

        if ($src === '') {
            $errors[] = new ValidationError('settings.src', 'Embed script URL is required.', 'required');
        } elseif (self::originOf($src) === null) {
            $errors[] = new ValidationError('settings.src', 'Embed script URL must be an absolute https URL.', 'invalid');
        } elseif ($origin !== '' && self::originOf($src) !== $origin) {
            $errors[] = new ValidationError('settings.src', 'Embed script URL must be served from the declared origin.', 'origin_mismatch');
        }

        if ($integrity === '') {
            $errors[] = new ValidationError('settings.integrity', 'Subresource Integrity (SRI) hash is required.', 'required');
        } elseif (preg_match(self::INTEGRITY_PATTERN, $integrity) !== 1) {
            $errors[] = new ValidationError('settings.integrity', 'Integrity must be one or more sha256/sha384/sha512 base64 hashes.', 'invalid');
        }

        $rawAttributes = $settings['attributes'] ?? null;
        if ($rawAttributes !== null) {
            if (!is_array($rawAttributes)) {
                $errors[] = new ValidationError('settings.attributes', 'Attributes must be an object of data-* keys.', 'invalid');
            } else {
                if (count($rawAttributes) > self::MAX_ATTRIBUTES) {
                    $errors[] = new ValidationError('settings.attributes', 'Too many attributes.', 'too_many');
                }
                foreach ($rawAttributes as $name => $value) {
                    if (!is_string($name) || preg_match(self::DATA_ATTRIBUTE_PATTERN, strtolower($name)) !== 1) {
                        $errors[] = new ValidationError('settings.attributes', 'Only data-* attribute names are allowed.', 'invalid');
                        break;
                    }
                    if (!is_string($value)) {
                        $errors[] = new ValidationError('settings.attributes', 'Attribute values must be strings.', 'invalid');
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Read-path parse. Returns a validated value object, or `null` if the stored
     * settings are malformed (structurally identical to {@see validate()} but
     * without messages, so the renderer refuses rather than emits).
     *
     * @param array<string, mixed> $settings
     */
    public static function tryParse(array $settings): ?self
    {
        $origin = is_string($settings['origin'] ?? null) ? strtolower(trim($settings['origin'])) : '';
        $src = is_string($settings['src'] ?? null) ? trim($settings['src']) : '';
        $integrity = is_string($settings['integrity'] ?? null) ? trim($settings['integrity']) : '';

        if (preg_match(self::ORIGIN_PATTERN, $origin) !== 1) {
            return null;
        }
        if (self::originOf($src) !== $origin) {
            return null;
        }
        if (preg_match(self::INTEGRITY_PATTERN, $integrity) !== 1) {
            return null;
        }

        $attributes = [];
        $rawAttributes = $settings['attributes'] ?? null;
        if (is_array($rawAttributes)) {
            if (count($rawAttributes) > self::MAX_ATTRIBUTES) {
                return null;
            }
            foreach ($rawAttributes as $name => $value) {
                if (!is_string($name) || preg_match(self::DATA_ATTRIBUTE_PATTERN, strtolower($name)) !== 1) {
                    return null;
                }
                if (!is_string($value)) {
                    return null;
                }
                $attributes[strtolower($name)] = $value;
            }
        } elseif ($rawAttributes !== null) {
            return null;
        }

        return new self($origin, $src, $integrity, $attributes);
    }

    /**
     * The scheme+host+port origin of an https URL, lowercased, or `null` when the
     * URL is not a well-formed https URL with an explicit host and no credentials.
     */
    private static function originOf(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }
        if (($parts['scheme'] ?? '') !== 'https') {
            return null;
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        $host = $parts['host'] ?? '';
        if ($host === '') {
            return null;
        }

        $origin = 'https://' . strtolower($host);
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }
}

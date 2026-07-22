<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\PublicRecord\FloatingCta;
use NeNeRecords\PublicRecord\FloatingCtaIcons;

/**
 * Server-side validator for the `floating_cta` public setting (EPIC #982, P1).
 *
 * This is the trust boundary: the admin editor and API clients are convenience, but
 * only this guarantees the stored JSON is a well-formed, P1-safe floating-CTA config
 * before {@see \NeNeRecords\PublicRecord\FloatingCtaHtml} emits it verbatim into the
 * public shell. Mirrors the hand-written style of {@see \NeNeRecords\BlocksField\BlocksDocumentValidator}.
 *
 * Fail-closed (hub 2026-07-22): P1 accepts only `position` ∈ {br, bl} and
 * `trigger` = always; the reserved P2 values (`right-tab`, `bottom-bar`, `scrollN`,
 * `delayN`) are rejected so no half-wired UI can ship. `link.url` is checked against a
 * scheme allowlist (http/https/mailto/tel + site-relative) that rejects `javascript:`
 * and protocol-relative/backslash-authority forms — this is the SSR `href` security seam.
 */
final class FloatingCtaValidator
{
    /** @var list<string> P1 position presets. `right-tab`/`bottom-bar` are reserved for P2. */
    private const POSITIONS = ['br', 'bl'];

    /** @var list<string> Supported triggers. `delay` = pure-CSS reveal (#982 P2 d); `scroll` reserved. */
    private const TRIGGERS = ['always', 'delay'];

    private const MAX_ICON_LEN = 16;
    private const MAX_LABEL_LEN = 60;
    private const MAX_SUB_LEN = 80;
    private const MAX_URL_LEN = 2048;
    private const MAX_CONDITION_ITEMS = 50;
    private const MAX_CONDITION_LEN = 200;

    public function validate(string $json): void
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new ValidationException([
                new ValidationError('value', 'Floating CTA must be a JSON object.', 'invalid'),
            ]);
        }

        $errors = [];

        $enabled = $decoded['enabled'] ?? false;
        if (!is_bool($enabled)) {
            $errors[] = new ValidationError('value.enabled', 'enabled must be a boolean.', 'invalid');
            $enabled = false;
        }

        // position / trigger: fail-closed enum (unknown or reserved-P2 value → reject).
        $position = $decoded['position'] ?? 'br';
        if (!is_string($position) || !in_array($position, self::POSITIONS, true)) {
            $errors[] = new ValidationError('value.position', 'position must be one of: ' . implode(', ', self::POSITIONS) . '.', 'invalid');
        }

        $trigger = $decoded['trigger'] ?? 'always';
        if (!is_string($trigger) || !in_array($trigger, self::TRIGGERS, true)) {
            $errors[] = new ValidationError('value.trigger', 'trigger must be one of: ' . implode(', ', self::TRIGGERS) . ' (others are not available yet).', 'invalid');
        }

        // triggerValue is the delay in seconds and is required (1–60) for the 'delay' trigger;
        // for 'always' it is ignored, so only reject a wrong-typed value when present.
        if ($trigger === 'delay') {
            $triggerValue = $decoded['triggerValue'] ?? null;
            if (!is_int($triggerValue) || $triggerValue < 1 || $triggerValue > FloatingCta::MAX_DELAY_SECONDS) {
                $errors[] = new ValidationError('value.triggerValue', 'triggerValue must be an integer between 1 and ' . FloatingCta::MAX_DELAY_SECONDS . ' seconds for the delay trigger.', 'invalid');
            }
        } elseif (isset($decoded['triggerValue']) && !is_int($decoded['triggerValue'])) {
            $errors[] = new ValidationError('value.triggerValue', 'triggerValue must be an integer.', 'invalid');
        }

        if (isset($decoded['accent'])) {
            if (!is_string($decoded['accent']) || preg_match('/^#[0-9A-Fa-f]{6}$/', $decoded['accent']) !== 1) {
                $errors[] = new ValidationError('value.accent', 'accent must be a #RRGGBB hex color.', 'invalid');
            }
        }

        // bottomOffset (#982 P2 (c)): page-bottom clearance in px reserved for the FAB.
        if (isset($decoded['bottomOffset'])) {
            $offset = $decoded['bottomOffset'];
            if (!is_int($offset) || $offset < 0 || $offset > FloatingCta::MAX_BOTTOM_OFFSET) {
                $errors[] = new ValidationError('value.bottomOffset', 'bottomOffset must be an integer between 0 and ' . FloatingCta::MAX_BOTTOM_OFFSET . '.', 'invalid');
            }
        }

        // dismissible (#982 P2 (a)): whether the FAB shows a "×" and remembers dismissal.
        if (isset($decoded['dismissible']) && !is_bool($decoded['dismissible'])) {
            $errors[] = new ValidationError('value.dismissible', 'dismissible must be a boolean.', 'invalid');
        }

        $content = $decoded['content'] ?? [];
        if (!is_array($content) || array_is_list($content)) {
            $errors[] = new ValidationError('value.content', 'content must be an object.', 'invalid');
            $content = [];
        }
        $this->validateOptionalString('value.content.icon', $content['icon'] ?? null, self::MAX_ICON_LEN, $errors);
        // iconId (P2) selects a curated first-party SVG; fail-closed against the shipped set.
        // The allowed enum is derived from FloatingCtaIcons::keys() so it can never drift.
        $iconId = $content['iconId'] ?? null;
        if ($iconId !== null && $iconId !== '' && (!is_string($iconId) || !FloatingCtaIcons::has($iconId))) {
            $errors[] = new ValidationError('value.content.iconId', 'iconId must be one of: ' . implode(', ', FloatingCtaIcons::keys()) . '.', 'invalid');
        }
        $this->validateOptionalString('value.content.sub', $content['sub'] ?? null, self::MAX_SUB_LEN, $errors);
        $label = $content['label'] ?? null;
        $this->validateOptionalString('value.content.label', $label, self::MAX_LABEL_LEN, $errors);

        $link = $decoded['link'] ?? [];
        if (!is_array($link) || array_is_list($link)) {
            $errors[] = new ValidationError('value.link', 'link must be an object.', 'invalid');
            $link = [];
        }
        $url = $link['url'] ?? null;
        // An empty url is the disabled-default shape; only shape-check a non-empty value
        // (the "required when enabled" check below rejects an empty url on an enabled CTA).
        if ($url !== null && $url !== '' && (!is_string($url) || strlen($url) > self::MAX_URL_LEN || !$this->isSafeUrl($url))) {
            $errors[] = new ValidationError('value.link.url', 'url must be http(s), mailto, tel, or a site-relative path (#/...).', 'invalid');
        }
        if (isset($link['newTab']) && !is_bool($link['newTab'])) {
            $errors[] = new ValidationError('value.link.newTab', 'newTab must be a boolean.', 'invalid');
        }

        $this->validateConditions($decoded['conditions'] ?? null, $errors);

        // When enabled, a label and a usable link are required (an enabled CTA with no
        // label / link would render nothing — reject rather than silently no-op).
        if ($enabled === true) {
            if (!is_string($label) || trim($label) === '') {
                $errors[] = new ValidationError('value.content.label', 'label is required when the floating CTA is enabled.', 'required');
            }
            if (!is_string($url) || trim($url) === '') {
                $errors[] = new ValidationError('value.link.url', 'url is required when the floating CTA is enabled.', 'required');
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private function validateConditions(mixed $conditions, array &$errors): void
    {
        if ($conditions === null) {
            return;
        }
        if (!is_array($conditions) || array_is_list($conditions)) {
            $errors[] = new ValidationError('value.conditions', 'conditions must be an object.', 'invalid');

            return;
        }

        foreach (['types', 'urlGlobs', 'exclude'] as $key) {
            if (!isset($conditions[$key])) {
                continue;
            }
            $list = $conditions[$key];
            if (!is_array($list) || !array_is_list($list)) {
                $errors[] = new ValidationError("value.conditions.{$key}", "{$key} must be an array of strings.", 'invalid');

                continue;
            }
            if (count($list) > self::MAX_CONDITION_ITEMS) {
                $errors[] = new ValidationError("value.conditions.{$key}", "{$key} may contain at most " . self::MAX_CONDITION_ITEMS . ' items.', 'invalid');
            }
            foreach ($list as $i => $item) {
                if (!is_string($item) || strlen($item) > self::MAX_CONDITION_LEN) {
                    $errors[] = new ValidationError("value.conditions.{$key}[{$i}]", 'Each entry must be a string (max ' . self::MAX_CONDITION_LEN . ' chars).', 'invalid');
                }
            }
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private function validateOptionalString(string $field, mixed $value, int $max, array &$errors): void
    {
        if ($value !== null && (!is_string($value) || strlen($value) > $max)) {
            $errors[] = new ValidationError($field, "Field must be a string (max {$max} chars).", 'invalid');
        }
    }

    /**
     * Allowlist safe link targets; blocks `javascript:`/`data:` and protocol-relative
     * `//host` / backslash-authority forms. P1 allows http(s)/mailto/tel + site-relative.
     */
    private function isSafeUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return false;
        }

        $first = $trimmed[0];
        $second = $trimmed[1] ?? '';
        if (($first === '/' || $first === '\\') && ($second === '/' || $second === '\\')) {
            return false;
        }

        return preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $trimmed) === 1;
    }
}

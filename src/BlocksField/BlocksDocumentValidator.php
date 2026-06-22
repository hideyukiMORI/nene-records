<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

/**
 * Server-side validator for a blocks document — the value of a `blocks` field
 * (#486). This is the trust boundary: the admin editor and MCP write tools are
 * convenience, but only this guarantees a stored document is a well-formed list
 * of curated, typed blocks. Mirrors the hand-written style of
 * {@see \NeNeRecords\Theme\ThemeManifestValidator}; the canonical shape lives in
 * docs/blocks/blocks.schema.json (frontend Ajv source).
 *
 * A document is `[{ id, type, data }]`. Each `type` must be whitelisted
 * ({@see BlockTypes}) and its `data` must match that type's shape.
 */
final class BlocksDocumentValidator
{
    public const MAX_BLOCKS = 200;
    private const MAX_ID_LEN = 64;
    private const MAX_TITLE_LEN = 200;
    private const MAX_TEXT_LEN = 50000;

    /** @var list<string> */
    private const CALLOUT_KINDS = ['info', 'warn', 'ok', 'danger'];

    /** @var list<string> */
    private const HERO_VARIANTS = ['standard', 'minimal', 'fullbleed'];

    /** @var list<string> */
    private const GALLERY_LAYOUTS = ['carousel', 'grid'];

    /** @var list<string> */
    private const CHART_TYPES = ['bar', 'line'];

    /** Layout/container blocks that hold child blocks; cannot be nested in each other (depth 2). */
    private const CONTAINER_TYPES = ['group'];

    private const GROUP_TONES = ['plain', 'muted', 'card'];

    private const MAX_GROUP_CHILDREN = 30;

    private const MAX_GALLERY_ITEMS = 50;
    private const MAX_SERIES_POINTS = 60;
    private const MAX_SERIES_LABEL_LEN = 120;
    private const MAX_HEADING_LEN = 300;
    private const MAX_LEAD_LEN = 2000;
    private const MAX_CTA_LABEL_LEN = 120;
    private const MAX_URL_LEN = 2000;

    /**
     * @throws ValidationException when the document is malformed or a block is invalid
     */
    public function validate(string $json): void
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new ValidationException([
                new ValidationError('value', 'Blocks document must be a JSON array of blocks.', 'invalid'),
            ]);
        }

        if (count($decoded) > self::MAX_BLOCKS) {
            throw new ValidationException([
                new ValidationError('value', 'A blocks document may contain at most ' . self::MAX_BLOCKS . ' blocks.', 'invalid'),
            ]);
        }

        $errors = [];
        $count = 0;

        foreach ($decoded as $index => $block) {
            $this->validateBlock("value[{$index}]", $block, true, $count, $errors);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private function validateBlock(string $path, mixed $block, bool $allowContainers, int &$count, array &$errors): void
    {
        // Cap total nodes including nested children; add the overflow error once.
        if (++$count > self::MAX_BLOCKS) {
            if ($count === self::MAX_BLOCKS + 1) {
                $errors[] = new ValidationError('value', 'A blocks document may contain at most ' . self::MAX_BLOCKS . ' blocks (including nested).', 'invalid');
            }

            return;
        }

        if (!is_array($block)) {
            $errors[] = new ValidationError($path, 'Each block must be an object.', 'invalid');

            return;
        }

        $id = $block['id'] ?? null;
        if (!is_string($id) || $id === '' || strlen($id) > self::MAX_ID_LEN) {
            $errors[] = new ValidationError("{$path}.id", 'Block id must be a non-empty string (max ' . self::MAX_ID_LEN . ' chars).', 'invalid');
        }

        $type = $block['type'] ?? null;
        if (!is_string($type) || !BlockTypes::isValid($type)) {
            $errors[] = new ValidationError("{$path}.type", 'Block type must be one of: ' . implode(', ', BlockTypes::all()) . '.', 'invalid');

            return;
        }

        if (!$allowContainers && in_array($type, self::CONTAINER_TYPES, true)) {
            $errors[] = new ValidationError("{$path}.type", 'Container blocks cannot be nested inside another container.', 'invalid');

            return;
        }

        $data = $block['data'] ?? null;
        if (!is_array($data)) {
            $errors[] = new ValidationError("{$path}.data", 'Block data must be an object.', 'invalid');

            return;
        }

        match ($type) {
            'text' => $this->validateTextData($path, $data, $errors),
            'callout' => $this->validateCalloutData($path, $data, $errors),
            'hero' => $this->validateHeroData($path, $data, $errors),
            'gallery' => $this->validateGalleryData($path, $data, $errors),
            'chart' => $this->validateChartData($path, $data, $errors),
            'group' => $this->validateGroupData($path, $data, $count, $errors),
            default => null,
        };
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateGroupData(string $path, array $data, int &$count, array &$errors): void
    {
        $tone = $data['tone'] ?? null;
        if (!is_string($tone) || !in_array($tone, self::GROUP_TONES, true)) {
            $errors[] = new ValidationError("{$path}.data.tone", 'Group tone must be one of: ' . implode(', ', self::GROUP_TONES) . '.', 'invalid');
        }

        $children = $data['children'] ?? null;
        if (!is_array($children) || !array_is_list($children)) {
            $errors[] = new ValidationError("{$path}.data.children", 'Group children must be an array of blocks.', 'invalid');

            return;
        }

        if (count($children) > self::MAX_GROUP_CHILDREN) {
            $errors[] = new ValidationError("{$path}.data.children", 'A group may contain at most ' . self::MAX_GROUP_CHILDREN . ' blocks.', 'invalid');

            return;
        }

        foreach ($children as $i => $child) {
            // Children are leaf blocks only (no container-in-container) → depth capped at 2.
            $this->validateBlock("{$path}.data.children[{$i}]", $child, false, $count, $errors);
        }
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateTextData(string $path, array $data, array &$errors): void
    {
        $markdown = $data['markdown'] ?? null;
        if (!is_string($markdown) || $markdown === '' || strlen($markdown) > self::MAX_TEXT_LEN) {
            $errors[] = new ValidationError("{$path}.data.markdown", 'Text block requires non-empty markdown (max ' . self::MAX_TEXT_LEN . ' chars).', 'invalid');
        }
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateCalloutData(string $path, array $data, array &$errors): void
    {
        $kind = $data['kind'] ?? null;
        if (!is_string($kind) || !in_array($kind, self::CALLOUT_KINDS, true)) {
            $errors[] = new ValidationError("{$path}.data.kind", 'Callout kind must be one of: ' . implode(', ', self::CALLOUT_KINDS) . '.', 'invalid');
        }

        $body = $data['body'] ?? null;
        if (!is_string($body) || $body === '' || strlen($body) > self::MAX_TEXT_LEN) {
            $errors[] = new ValidationError("{$path}.data.body", 'Callout block requires a non-empty body (max ' . self::MAX_TEXT_LEN . ' chars).', 'invalid');
        }

        $title = $data['title'] ?? null;
        if ($title !== null && (!is_string($title) || strlen($title) > self::MAX_TITLE_LEN)) {
            $errors[] = new ValidationError("{$path}.data.title", 'Callout title must be a string (max ' . self::MAX_TITLE_LEN . ' chars).', 'invalid');
        }
    }

    /**
     * Hero block (#486 S2): a kicker/heading/lead with up to two CTAs. Reuses the
     * existing `.hero__*` presentation on the consumer (variant via `data-hero`).
     * Image art is a later slice (S3 media picker), so no media field here.
     *
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateHeroData(string $path, array $data, array &$errors): void
    {
        $variant = $data['variant'] ?? null;
        if (!is_string($variant) || !in_array($variant, self::HERO_VARIANTS, true)) {
            $errors[] = new ValidationError("{$path}.data.variant", 'Hero variant must be one of: ' . implode(', ', self::HERO_VARIANTS) . '.', 'invalid');
        }

        $heading = $data['heading'] ?? null;
        if (!is_string($heading) || $heading === '' || strlen($heading) > self::MAX_HEADING_LEN) {
            $errors[] = new ValidationError("{$path}.data.heading", 'Hero requires a non-empty heading (max ' . self::MAX_HEADING_LEN . ' chars).', 'invalid');
        }

        $this->validateOptionalString("{$path}.data.kicker", $data['kicker'] ?? null, self::MAX_TITLE_LEN, $errors);
        $this->validateOptionalString("{$path}.data.lead", $data['lead'] ?? null, self::MAX_LEAD_LEN, $errors);
        $this->validateOptionalString("{$path}.data.ctaLabel", $data['ctaLabel'] ?? null, self::MAX_CTA_LABEL_LEN, $errors);
        $this->validateOptionalString("{$path}.data.ghostLabel", $data['ghostLabel'] ?? null, self::MAX_CTA_LABEL_LEN, $errors);
        $this->validateOptionalUrl("{$path}.data.ctaUrl", $data['ctaUrl'] ?? null, $errors);
        $this->validateOptionalUrl("{$path}.data.ghostUrl", $data['ghostUrl'] ?? null, $errors);

        $media = $data['media'] ?? null;
        if ($media !== null) {
            $this->validateMedia("{$path}.data.media", $media, $errors);
        }
    }

    /**
     * Picker-selected library image: a site-relative `/media/...` URL (rendered
     * directly on the consumer; the media metadata API is admin-only) plus its
     * mediaId for provenance and an optional alt (C4 SEO/a11y).
     *
     * @param list<ValidationError> $errors
     */
    private function validateMedia(string $field, mixed $media, array &$errors): void
    {
        if (!is_array($media)) {
            $errors[] = new ValidationError($field, 'Media must be an object.', 'invalid');

            return;
        }

        $mediaId = $media['mediaId'] ?? null;
        if (!is_string($mediaId) || $mediaId === '' || strlen($mediaId) > self::MAX_ID_LEN) {
            $errors[] = new ValidationError("{$field}.mediaId", 'Media requires a non-empty mediaId.', 'invalid');
        }

        $url = $media['url'] ?? null;
        if (!is_string($url) || strlen($url) > self::MAX_URL_LEN || !$this->isSafeMediaUrl($url)) {
            $errors[] = new ValidationError("{$field}.url", 'Media url must be a same-origin /media path or an https URL.', 'invalid');
        }

        $alt = $media['alt'] ?? null;
        if ($alt !== null && (!is_string($alt) || strlen($alt) > self::MAX_HEADING_LEN)) {
            $errors[] = new ValidationError("{$field}.alt", 'Media alt must be a string (max ' . self::MAX_HEADING_LEN . ' chars).', 'invalid');
        }
    }

    /**
     * Gallery block (#486 S4): an ordered list of library images shown as a
     * scroll-snap carousel (no-JS) or a grid. Every item needs a non-empty alt
     * (C4 SEO/a11y); url must be a same-origin /media path.
     *
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateGalleryData(string $path, array $data, array &$errors): void
    {
        $layout = $data['layout'] ?? null;
        if (!is_string($layout) || !in_array($layout, self::GALLERY_LAYOUTS, true)) {
            $errors[] = new ValidationError("{$path}.data.layout", 'Gallery layout must be one of: ' . implode(', ', self::GALLERY_LAYOUTS) . '.', 'invalid');
        }

        $items = $data['items'] ?? null;
        if (!is_array($items) || !array_is_list($items) || $items === []) {
            $errors[] = new ValidationError("{$path}.data.items", 'Gallery requires at least one image.', 'invalid');

            return;
        }

        if (count($items) > self::MAX_GALLERY_ITEMS) {
            $errors[] = new ValidationError("{$path}.data.items", 'Gallery may contain at most ' . self::MAX_GALLERY_ITEMS . ' images.', 'invalid');

            return;
        }

        foreach ($items as $index => $item) {
            $this->validateGalleryItem("{$path}.data.items[{$index}]", $item, $errors);
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private function validateGalleryItem(string $field, mixed $item, array &$errors): void
    {
        if (!is_array($item)) {
            $errors[] = new ValidationError($field, 'Gallery item must be an object.', 'invalid');

            return;
        }

        $mediaId = $item['mediaId'] ?? null;
        if (!is_string($mediaId) || $mediaId === '' || strlen($mediaId) > self::MAX_ID_LEN) {
            $errors[] = new ValidationError("{$field}.mediaId", 'Gallery item requires a non-empty mediaId.', 'invalid');
        }

        $url = $item['url'] ?? null;
        if (!is_string($url) || strlen($url) > self::MAX_URL_LEN || !$this->isSafeMediaUrl($url)) {
            $errors[] = new ValidationError("{$field}.url", 'Gallery item url must be a same-origin /media path or an https URL.', 'invalid');
        }

        $alt = $item['alt'] ?? null;
        if (!is_string($alt) || $alt === '' || strlen($alt) > self::MAX_HEADING_LEN) {
            $errors[] = new ValidationError("{$field}.alt", 'Gallery item requires alt text (C4).', 'invalid');
        }

        $caption = $item['caption'] ?? null;
        if ($caption !== null && (!is_string($caption) || strlen($caption) > self::MAX_HEADING_LEN)) {
            $errors[] = new ValidationError("{$field}.caption", 'Gallery caption must be a string (max ' . self::MAX_HEADING_LEN . ' chars).', 'invalid');
        }
    }

    /**
     * Chart block (#486 S5): a first-party bar/line chart over labelled numeric
     * points, with a required one-line summary projected sr-only (C4) alongside a
     * data table on the consumer.
     *
     * @param array<array-key, mixed> $data
     * @param list<ValidationError> $errors
     */
    private function validateChartData(string $path, array $data, array &$errors): void
    {
        $chartType = $data['chartType'] ?? null;
        if (!is_string($chartType) || !in_array($chartType, self::CHART_TYPES, true)) {
            $errors[] = new ValidationError("{$path}.data.chartType", 'Chart type must be one of: ' . implode(', ', self::CHART_TYPES) . '.', 'invalid');
        }

        $this->validateOptionalString("{$path}.data.title", $data['title'] ?? null, self::MAX_HEADING_LEN, $errors);

        $summary = $data['summary'] ?? null;
        if (!is_string($summary) || $summary === '' || strlen($summary) > self::MAX_LEAD_LEN) {
            $errors[] = new ValidationError("{$path}.data.summary", 'Chart requires a non-empty summary (C4).', 'invalid');
        }

        $series = $data['series'] ?? null;
        if (!is_array($series) || !array_is_list($series) || count($series) < 2) {
            $errors[] = new ValidationError("{$path}.data.series", 'Chart requires at least two data points.', 'invalid');

            return;
        }

        if (count($series) > self::MAX_SERIES_POINTS) {
            $errors[] = new ValidationError("{$path}.data.series", 'Chart may contain at most ' . self::MAX_SERIES_POINTS . ' data points.', 'invalid');

            return;
        }

        foreach ($series as $index => $point) {
            $this->validateSeriesPoint("{$path}.data.series[{$index}]", $point, $errors);
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private function validateSeriesPoint(string $field, mixed $point, array &$errors): void
    {
        if (!is_array($point)) {
            $errors[] = new ValidationError($field, 'Data point must be an object.', 'invalid');

            return;
        }

        $label = $point['label'] ?? null;
        if (!is_string($label) || $label === '' || strlen($label) > self::MAX_SERIES_LABEL_LEN) {
            $errors[] = new ValidationError("{$field}.label", 'Data point requires a non-empty label.', 'invalid');
        }

        $value = $point['value'] ?? null;
        if (!is_int($value) && !is_float($value)) {
            $errors[] = new ValidationError("{$field}.value", 'Data point value must be a number.', 'invalid');
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
     * @param list<ValidationError> $errors
     */
    private function validateOptionalUrl(string $field, mixed $value, array &$errors): void
    {
        if ($value === null) {
            return;
        }

        if (!is_string($value) || strlen($value) > self::MAX_URL_LEN || !$this->isSafeUrl($value)) {
            $errors[] = new ValidationError($field, 'URL must be http(s), mailto, or a site-relative path (#/...).', 'invalid');
        }
    }

    /**
     * Allowlist safe link targets; blocks `javascript:`/`data:` (script execution)
     * and protocol-relative `//host` / backslash-authority `/\host` forms that the
     * browser resolves cross-origin (open redirect / phishing).
     */
    private function isSafeUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return true;
        }

        if ($this->hasAuthorityPrefix($trimmed)) {
            return false;
        }

        return preg_match('#^(https?://|mailto:|/|\#)#i', $trimmed) === 1;
    }

    /**
     * Library image url: a same-origin relative `/...` path (local driver) or an
     * `https://` absolute url (object-storage / CDN driver). Rejects protocol-
     * relative / backslash-authority forms and insecure schemes.
     */
    private function isSafeMediaUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '' || $this->hasAuthorityPrefix($trimmed)) {
            return false;
        }
        if (str_starts_with($trimmed, 'https://')) {
            return true;
        }

        return str_starts_with($trimmed, '/');
    }

    /** True when the first two chars are both '/' or '\' (protocol-relative authority). */
    private function hasAuthorityPrefix(string $url): bool
    {
        if (strlen($url) < 2) {
            return false;
        }
        $a = $url[0];
        $b = $url[1];

        return ($a === '/' || $a === '\\') && ($b === '/' || $b === '\\');
    }
}

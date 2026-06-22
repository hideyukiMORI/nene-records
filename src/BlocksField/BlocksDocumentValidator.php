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

    private const MAX_GALLERY_ITEMS = 50;
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

        foreach ($decoded as $index => $block) {
            $this->validateBlock($index, $block, $errors);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param list<ValidationError> $errors
     */
    private function validateBlock(int $index, mixed $block, array &$errors): void
    {
        $path = "value[{$index}]";

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
            default => null,
        };
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
        if (!is_string($url) || $url === '' || strlen($url) > self::MAX_URL_LEN || !str_starts_with($url, '/')) {
            $errors[] = new ValidationError("{$field}.url", 'Media url must be a site-relative path (e.g. /media/...).', 'invalid');
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
        if (!is_string($url) || $url === '' || strlen($url) > self::MAX_URL_LEN || !str_starts_with($url, '/')) {
            $errors[] = new ValidationError("{$field}.url", 'Gallery item url must be a site-relative path (e.g. /media/...).', 'invalid');
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
     * Allowlist safe link targets; blocks `javascript:`/`data:` and other schemes
     * that could execute script when rendered as an href.
     */
    private function isSafeUrl(string $url): bool
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return true;
        }

        return preg_match('#^(https?://|mailto:|/|\#)#i', $trimmed) === 1;
    }
}

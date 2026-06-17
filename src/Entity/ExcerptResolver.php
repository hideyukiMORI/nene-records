<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\TextField\TextFieldRepositoryInterface;

/**
 * Resolves a plain-text excerpt for a page of listed entities, applying the
 * site's `excerpt_source` / `excerpt_length` settings:
 *  - meta : the SEO meta description.
 *  - body : the markdown `body` field, stripped to text.
 *  - auto : meta description if set, otherwise the body (default).
 *
 * Body values are fetched once for the whole page (findByEntityIds), so this
 * stays a single extra query regardless of page size.
 */
final readonly class ExcerptResolver
{
    private const BODY_FIELD_KEY = 'body';

    public function __construct(
        private TextFieldRepositoryInterface $textFields,
        private SettingRepositoryInterface $settings,
    ) {
    }

    /**
     * @param list<ListEntityItem> $items
     *
     * @return array<int, string> entityId => excerpt
     */
    public function resolve(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $source = $this->setting('excerpt_source', 'auto');
        $length = (int) $this->setting('excerpt_length', '160');
        if ($length <= 0) {
            $length = 160;
        }

        $bodyByEntity = $source === 'meta' ? [] : $this->loadBodies($items);

        $excerpts = [];
        foreach ($items as $item) {
            $meta = trim($item->metaDescription ?? '');
            $body = $bodyByEntity[$item->id] ?? '';
            $excerpts[$item->id] = match ($source) {
                'meta' => MarkdownExcerpt::truncate($meta, $length),
                'body' => MarkdownExcerpt::fromMarkdown($body, $length),
                default => $meta !== ''
                    ? MarkdownExcerpt::truncate($meta, $length)
                    : MarkdownExcerpt::fromMarkdown($body, $length),
            };
        }

        return $excerpts;
    }

    /**
     * @param list<ListEntityItem> $items
     *
     * @return array<int, string> entityId => markdown body value
     */
    private function loadBodies(array $items): array
    {
        $ids = array_map(static fn (ListEntityItem $i): int => $i->id, $items);
        $bodies = [];
        foreach ($this->textFields->findByEntityIds($ids) as $field) {
            if ($field->fieldKey === self::BODY_FIELD_KEY && !isset($bodies[$field->entityId])) {
                $bodies[$field->entityId] = $field->value;
            }
        }

        return $bodies;
    }

    private function setting(string $key, string $default): string
    {
        $value = $this->settings->findValueByKey($key);
        if ($value !== null && $value->value !== null && trim($value->value) !== '') {
            return trim($value->value);
        }

        $def = $this->settings->findDefByKey($key);
        if ($def !== null && $def->defaultValue !== null) {
            return $def->defaultValue;
        }

        return $default;
    }
}

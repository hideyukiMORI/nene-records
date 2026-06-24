<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityTag\EntityTagRepositoryInterface;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\PublicRecord\PublicPermalinkResolver;
use NeNeRecords\Tag\Tag;
use NeNeRecords\Tag\TagRepositoryInterface;
use NeNeRecords\TextField\TextField;
use NeNeRecords\TextField\TextFieldRepositoryInterface;
use NeNeRecords\UrlRedirect\UrlRedirectRepositoryInterface;

/**
 * Executes a parsed WXR import (S2/S3): creates entities (posts/pages), their
 * title/body text fields, and tags + entity-tag links, scoped to the active
 * organization via the repositories' org-aware writes.
 *
 * - Idempotent: an item whose slug already exists for its type is skipped, so
 *   re-running the same import does not duplicate.
 * - Imported content is HTML, so it is written to an html-typed field (see
 *   {@see resolveContentField}): a fresh type's `body` is created/promoted to
 *   html; a type that already has native (e.g. markdown) content keeps it and the
 *   import lands in a dedicated `wp_content` html field. Either way the content
 *   renders faithfully (sanitized) on SSR and SPA rather than being stripped.
 * - 301 redirect map (S3): each item's original WordPress URL path is recorded
 *   as a redirect to the new permalink, preserving SEO equity after migration.
 * - Media (S4): a caller-supplied old→new media URL map rewrites `<img src>`
 *   references in the body so imported content points at the new media library.
 *
 * Out of scope (later slices): postmeta.
 */
final class WxrImportExecutor
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private FieldDefRepositoryInterface $fieldDefs,
        private EntityRepositoryInterface $entities,
        private TextFieldRepositoryInterface $textFields,
        private TagRepositoryInterface $tags,
        private EntityTagRepositoryInterface $entityTags,
        private UrlRedirectRepositoryInterface $redirects,
    ) {
    }

    /**
     * @param array<string, string> $mediaUrlMap original attachment URL → new media URL,
     *                                            used to rewrite `<img src>` in body content
     */
    public function execute(WxrDocument $document, array $mediaUrlMap = []): WxrImportResult
    {
        $plan = (new WxrImportPlanner())->plan($document);
        $tagNames = $this->termNameMap($document);

        /** @var array<string, EntityType> $typeBySlug */
        $typeBySlug = [];
        /** @var array<string, string> $contentFieldBySlug */
        $contentFieldBySlug = [];
        /** @var array<string, int> $tagIdBySlug */
        $tagIdBySlug = [];
        /** @var list<string> $warnings */
        $warnings = [];

        $created = 0;
        $skippedExisting = 0;
        $tagLinks = 0;
        $redirectsCreated = 0;

        foreach ($plan->plannedItems as $item) {
            $type = $typeBySlug[$item->entityTypeSlug] ??= $this->resolveType($item->entityTypeSlug);
            $typeId = $type->id;
            if ($typeId === null) {
                continue; // resolveType always sets the id; guard satisfies the analyser
            }

            $contentField = $contentFieldBySlug[$item->entityTypeSlug]
                ??= $this->resolveContentField($typeId, $type->slug, $warnings);

            if ($this->entities->findBySlug($item->slug, $typeId) !== null) {
                ++$skippedExisting;
                continue;
            }

            $publishedAt = $item->publishedAtIso !== null ? new DateTimeImmutable($item->publishedAtIso) : null;

            $entityId = $this->entities->save(new Entity(
                id: null,
                entityTypeId: $typeId,
                slug: $item->slug,
                status: EntityStatus::from($item->status),
                publishedAt: $publishedAt,
            ));

            // Imported WordPress content is HTML — write it to an html-typed field
            // so it renders faithfully (sanitized) on both SSR and SPA.
            $body = $mediaUrlMap === [] ? $item->contentHtml : strtr($item->contentHtml, $mediaUrlMap);
            $this->textFields->save(new TextField(entityId: $entityId, fieldKey: 'title', value: $item->title));
            $this->textFields->save(new TextField(entityId: $entityId, fieldKey: $contentField, value: $body));

            foreach ($item->tagSlugs as $tagSlug) {
                $tagId = $tagIdBySlug[$tagSlug] ??= $this->ensureTag($tagSlug, $tagNames[$tagSlug] ?? $tagSlug);
                if (!$this->entityTags->isAttached($entityId, $tagId)) {
                    $this->entityTags->attach($entityId, $tagId);
                    ++$tagLinks;
                }
            }

            if ($this->recordRedirect($item, $type, $entityId, $publishedAt)) {
                ++$redirectsCreated;
            }

            ++$created;
        }

        return new WxrImportResult(
            createdEntities: $created,
            skippedExisting: $skippedExisting,
            tagsEnsured: count($tagIdBySlug),
            tagLinks: $tagLinks,
            redirectsCreated: $redirectsCreated,
            skippedItems: $plan->skippedItems,
            warnings: [...$plan->warnings, ...$warnings],
        );
    }

    private function resolveType(string $slug): EntityType
    {
        $existing = $this->entityTypes->findBySlug($slug);

        if ($existing !== null && $existing->id !== null) {
            $type = $existing;
            $typeId = $existing->id;
        } else {
            $typeId = $this->entityTypes->save(new EntityType(name: ucfirst($slug), slug: $slug));
            $type = new EntityType(name: ucfirst($slug), slug: $slug, id: $typeId);
        }

        $this->ensureFieldDef($typeId, 'title', 'text');

        return $type;
    }

    /**
     * Resolve the html-typed field that imported WordPress content is written to,
     * so it renders faithfully (sanitized) instead of being stripped by a markdown
     * field. Returns the field key to write the content into.
     *
     * - `body` is html or absent → use (create) `body` as html.
     * - `body` exists as non-html but the type has no entities yet (a fresh
     *   migration target) → promote `body` to html (safe — no values to break).
     * - `body` is non-html and the type already has native content → write to a
     *   dedicated `wp_content` html field so existing markdown authoring is intact.
     *
     * @param list<string> $warnings accumulates operator-visible notes (by reference)
     */
    private function resolveContentField(int $typeId, string $typeSlug, array &$warnings): string
    {
        $body = $this->fieldDefs->findByEntityTypeIdAndFieldKey($typeId, 'body');

        if ($body === null) {
            $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: 'body', dataType: 'html'));

            return 'body';
        }

        if ($body->dataType === 'html') {
            return 'body';
        }

        if ($this->entities->countByCriteria(new EntityListCriteria(entityTypeId: $typeId)) === 0) {
            $this->fieldDefs->update(new FieldDef(
                entityTypeId: $body->entityTypeId,
                fieldKey: $body->fieldKey,
                dataType: 'html',
                id: $body->id,
                targetEntityTypeId: $body->targetEntityTypeId,
                cardinality: $body->cardinality,
                region: $body->region,
                displayOrder: $body->displayOrder,
            ));
            $warnings[] = "「{$typeSlug}」の body フィールドを html 型へ昇格しました（取込コンテンツは HTML のため）。";

            return 'body';
        }

        $this->ensureFieldDef($typeId, 'wp_content', 'html');
        $warnings[] = "「{$typeSlug}」は既存の body が html 型でないため、取込本文を wp_content フィールドへ格納しました。";

        return 'wp_content';
    }

    private function ensureFieldDef(int $typeId, string $fieldKey, string $dataType): void
    {
        if ($this->fieldDefs->findByEntityTypeIdAndFieldKey($typeId, $fieldKey) === null) {
            $this->fieldDefs->save(new FieldDef(entityTypeId: $typeId, fieldKey: $fieldKey, dataType: $dataType));
        }
    }

    private function ensureTag(string $slug, string $name): int
    {
        $existing = $this->tags->findBySlug($slug);

        if ($existing !== null && $existing->id !== null) {
            return $existing->id;
        }

        return $this->tags->save(new Tag(slug: $slug, name: $name));
    }

    /**
     * Record a 301 from the item's original WordPress URL path to its new
     * permalink. Returns true when a redirect was stored.
     */
    private function recordRedirect(
        WxrImportPlannedItem $item,
        EntityType $type,
        int $entityId,
        ?DateTimeImmutable $publishedAt,
    ): bool {
        if ($item->originalLink === null) {
            return false;
        }

        $source = $this->pathFromUrl($item->originalLink);
        if ($source === '') {
            return false;
        }

        $target = PublicPermalinkResolver::resolve(
            $type->permalinkPattern,
            $type->slug,
            $item->slug,
            $entityId,
            $publishedAt,
        );

        if ($source === $target) {
            return false;
        }

        $this->redirects->save($source, $target);

        return true;
    }

    /** Extract the normalized path (no host, no trailing slash) from a URL. */
    private function pathFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || $path === '' || $path === '/') {
            return '';
        }

        return rtrim($path, '/');
    }

    /** @return array<string, string> slug → display name from the WXR term definitions */
    private function termNameMap(WxrDocument $document): array
    {
        $map = [];
        foreach ($document->terms as $term) {
            if ($term->slug !== '' && $term->name !== '') {
                $map[$term->slug] = $term->name;
            }
        }

        return $map;
    }
}

<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityTag\EntityTagRepositoryInterface;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefRepositoryInterface;
use NeNeRecords\Tag\Tag;
use NeNeRecords\Tag\TagRepositoryInterface;
use NeNeRecords\TextField\TextField;
use NeNeRecords\TextField\TextFieldRepositoryInterface;

/**
 * Executes a parsed WXR import (S2): creates entities (posts/pages), their
 * title/body text fields, and tags + entity-tag links, scoped to the active
 * organization via the repositories' org-aware writes.
 *
 * - Idempotent: an item whose slug already exists for its type is skipped, so
 *   re-running the same import does not duplicate.
 * - Target type/fields are ensured (created if missing); `title` is text, `body`
 *   is html so imported WordPress content renders sanitized. A pre-existing
 *   markdown `body` is reused as-is (HTML stored verbatim; renders via the
 *   markdown HTML passthrough).
 *
 * Out of scope (later slices): media attachments, 301 redirect map, postmeta.
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
    ) {
    }

    public function execute(WxrDocument $document): WxrImportResult
    {
        $plan = (new WxrImportPlanner())->plan($document);
        $tagNames = $this->termNameMap($document);

        /** @var array<string, int> $typeIdBySlug */
        $typeIdBySlug = [];
        /** @var array<string, int> $tagIdBySlug */
        $tagIdBySlug = [];

        $created = 0;
        $skippedExisting = 0;
        $tagLinks = 0;

        foreach ($plan->plannedItems as $item) {
            $typeId = $typeIdBySlug[$item->entityTypeSlug] ??= $this->resolveType($item->entityTypeSlug);

            if ($this->entities->findBySlug($item->slug, $typeId) !== null) {
                ++$skippedExisting;
                continue;
            }

            $entityId = $this->entities->save(new Entity(
                id: null,
                entityTypeId: $typeId,
                slug: $item->slug,
                status: EntityStatus::from($item->status),
                publishedAt: $item->publishedAtIso !== null ? new DateTimeImmutable($item->publishedAtIso) : null,
            ));

            $this->textFields->save(new TextField(entityId: $entityId, fieldKey: 'title', value: $item->title));
            $this->textFields->save(new TextField(entityId: $entityId, fieldKey: 'body', value: $item->contentHtml));

            foreach ($item->tagSlugs as $tagSlug) {
                $tagId = $tagIdBySlug[$tagSlug] ??= $this->ensureTag($tagSlug, $tagNames[$tagSlug] ?? $tagSlug);
                if (!$this->entityTags->isAttached($entityId, $tagId)) {
                    $this->entityTags->attach($entityId, $tagId);
                    ++$tagLinks;
                }
            }

            ++$created;
        }

        return new WxrImportResult(
            createdEntities: $created,
            skippedExisting: $skippedExisting,
            tagsEnsured: count($tagIdBySlug),
            tagLinks: $tagLinks,
            skippedItems: $plan->skippedItems,
            warnings: $plan->warnings,
        );
    }

    private function resolveType(string $slug): int
    {
        $existing = $this->entityTypes->findBySlug($slug);

        if ($existing !== null && $existing->id !== null) {
            $typeId = $existing->id;
        } else {
            $typeId = $this->entityTypes->save(new EntityType(name: ucfirst($slug), slug: $slug));
        }

        $this->ensureFieldDef($typeId, 'title', 'text');
        $this->ensureFieldDef($typeId, 'body', 'html');

        return $typeId;
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

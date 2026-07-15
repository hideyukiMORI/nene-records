<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\TextField\TextFieldRepositoryInterface;

/**
 * Server-side data for an entity type's public archive (`/{typeSlug}`), so the
 * listing is crawlable rather than SPA-only (#877).
 *
 * The SPA already renders this page (`PublicBrowsePage` via
 * `usePublicBrowseEntityRecordsPage`) and replaces the SSR markup on mount, so
 * this use case deliberately mirrors that hook's query: published only, page size
 * {@see self::PAGE_SIZE}, and the repository's default id-descending order. If
 * those drift apart the crawler and the visitor see different lists.
 */
final readonly class GetPublicTypeArchiveUseCase
{
    /** Mirrors the SPA's PUBLIC_BROWSE_PAGE_SIZE / DEFAULT_LIST_PARAMS.limit. */
    public const PAGE_SIZE = 20;

    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private EntityRepositoryInterface $entities,
        private TextFieldRepositoryInterface $textFields,
    ) {
    }

    /** @return GetPublicTypeArchiveOutput|null null when no such type in this org */
    public function execute(string $typeSlug, int $offset = 0): ?GetPublicTypeArchiveOutput
    {
        $entityType = $this->entityTypes->findBySlug($typeSlug);

        if ($entityType === null || $entityType->id === null) {
            return null;
        }

        $criteria = new EntityListCriteria(
            entityTypeId: $entityType->id,
            status: EntityStatus::Published,
        );

        $offset = max(0, $offset);
        $total = $this->entities->countByCriteria($criteria);
        $entities = $this->entities->findByCriteria($criteria, self::PAGE_SIZE, $offset);

        $ids = [];
        foreach ($entities as $entity) {
            if ($entity->id !== null) {
                $ids[] = $entity->id;
            }
        }

        $rows = $ids === [] ? [] : $this->textFields->findByEntityIds($ids);

        $items = [];
        foreach ($entities as $entity) {
            if ($entity->id === null) {
                continue;
            }
            $items[] = new PublicTypeArchiveItem(
                id: $entity->id,
                label: RecordDisplayLabel::resolve(
                    $entity->id,
                    $rows,
                    $entity->metaTitle,
                    'Record #' . $entity->id,
                ),
                // The shared canonical builder, so an archive link always matches the
                // record's own canonical/og:url/sitemap entry (custom permalink wins).
                path: PublicPermalinkResolver::canonicalPath(
                    $entity->permalink,
                    $entityType->permalinkPattern,
                    $entityType->slug,
                    $entity->slug,
                    $entity->id,
                    $entity->publishedAt,
                ),
                publishedAt: $entity->publishedAt,
            );
        }

        return new GetPublicTypeArchiveOutput(
            typeSlug: $entityType->slug,
            typeName: $entityType->name,
            items: $items,
            total: $total,
            offset: $offset,
            pageSize: self::PAGE_SIZE,
        );
    }
}

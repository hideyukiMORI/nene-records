<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use DateTimeInterface;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

/**
 * Enumerates every published, non-deleted record in the current organization and
 * resolves it to its canonical permalink (the same {@see PublicPermalinkResolver}
 * the SSR pages use, so sitemap URLs match the pages' canonical/og:url).
 *
 * Records are read in pages per entity type and capped at the sitemap protocol's
 * 50,000-URL limit; `lastmod` is the record's update (or publish) time.
 */
final readonly class GenerateSitemapUseCase implements GenerateSitemapUseCaseInterface
{
    private const PAGE_SIZE = 1000;
    private const MAX_URLS = 50000;

    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private EntityRepositoryInterface $entities,
    ) {
    }

    /** @return list<SitemapUrl> */
    public function execute(): array
    {
        $urls = [];

        foreach ($this->entityTypes->findAll(self::PAGE_SIZE, 0) as $type) {
            if ($type->id === null) {
                continue;
            }

            $offset = 0;

            while (count($urls) < self::MAX_URLS) {
                $batch = $this->entities->findByCriteria(
                    new EntityListCriteria(entityTypeId: $type->id, status: EntityStatus::Published),
                    self::PAGE_SIZE,
                    $offset,
                );

                if ($batch === []) {
                    break;
                }

                foreach ($batch as $entity) {
                    if ($entity->id === null) {
                        continue;
                    }

                    $urls[] = new SitemapUrl(
                        PublicPermalinkResolver::resolve(
                            $type->permalinkPattern,
                            $type->slug,
                            $entity->slug,
                            $entity->id,
                            $entity->publishedAt,
                        ),
                        ($entity->updatedAt ?? $entity->publishedAt)?->format(DateTimeInterface::ATOM),
                    );

                    if (count($urls) >= self::MAX_URLS) {
                        return $urls;
                    }
                }

                if (count($batch) < self::PAGE_SIZE) {
                    break;
                }

                $offset += self::PAGE_SIZE;
            }
        }

        return $urls;
    }
}

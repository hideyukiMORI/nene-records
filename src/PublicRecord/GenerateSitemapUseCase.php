<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use DateTimeInterface;
use NeNeRecords\Entity\EntityListCriteria;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

/**
 * Enumerates published, non-deleted records in the current organization,
 * resolving each to its canonical permalink (the same {@see PublicPermalinkResolver}
 * the SSR pages use, so sitemap URLs match the pages' canonical/og:url).
 *
 * The global order is "entity types in display order, then records within each
 * type". {@see count()} and {@see page()} let {@see RenderSitemapHandler} split a
 * large sitemap across an index without loading every URL into memory: a page
 * only queries the rows in its window.
 */
final readonly class GenerateSitemapUseCase implements GenerateSitemapUseCaseInterface
{
    private const TYPE_PAGE = 1000;

    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
        private EntityRepositoryInterface $entities,
        private FrontPageSetting $frontPage,
    ) {
    }

    public function count(): int
    {
        $total = 0;

        foreach ($this->entityTypes->findAll(self::TYPE_PAGE, 0) as $type) {
            if ($type->id === null) {
                continue;
            }

            $total += $this->entities->countByCriteria($this->publishedIn($type->id));
        }

        return $total;
    }

    /** @return list<SitemapUrl> */
    public function page(int $offset, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $windowStart = max(0, $offset);
        $windowEnd = $windowStart + $limit;
        $seen = 0; // global index of the first record of the current type
        $urls = [];
        $frontPageId = $this->frontPage->pinnedRecordId();

        foreach ($this->entityTypes->findAll(self::TYPE_PAGE, 0) as $type) {
            if ($type->id === null) {
                continue;
            }

            $criteria = $this->publishedIn($type->id);
            $typeCount = $this->entities->countByCriteria($criteria);

            if ($typeCount === 0) {
                continue;
            }

            $typeStart = $seen;
            $typeEnd = $seen + $typeCount;
            $seen = $typeEnd;

            if ($typeStart >= $windowEnd) {
                break; // types are ordered: this and every later type is past the window.
            }

            if ($typeEnd <= $windowStart) {
                continue; // this type is entirely before the window.
            }

            $from = max($windowStart, $typeStart);
            $to = min($windowEnd, $typeEnd);

            foreach ($this->entities->findByCriteria($criteria, $to - $from, $from - $typeStart) as $entity) {
                if ($entity->id === null) {
                    continue;
                }

                // The front page lives at `/`, not its own permalink — emit it once as the
                // root so the sitemap carries no duplicate URL for it (#701).
                $path = $entity->id === $frontPageId
                    ? '/'
                    : PublicPermalinkResolver::canonicalPath(
                        $entity->permalink,
                        $type->permalinkPattern,
                        $type->slug,
                        $entity->slug,
                        $entity->id,
                        $entity->publishedAt,
                    );

                $urls[] = new SitemapUrl(
                    $path,
                    ($entity->updatedAt ?? $entity->publishedAt)?->format(DateTimeInterface::ATOM),
                );
            }

            if ($to >= $windowEnd) {
                break; // window fully satisfied.
            }
        }

        return $urls;
    }

    private function publishedIn(int $entityTypeId): EntityListCriteria
    {
        return new EntityListCriteria(entityTypeId: $entityTypeId, status: EntityStatus::Published);
    }
}

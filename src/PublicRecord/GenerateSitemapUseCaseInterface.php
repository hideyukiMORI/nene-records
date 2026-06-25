<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

interface GenerateSitemapUseCaseInterface
{
    /** Total published, non-deleted records in the current organization. */
    public function count(): int;

    /**
     * A window of published record URLs in the organization's stable global
     * order (entity types in display order, records within each type), resolved
     * through each type's permalink pattern. Used to page the sitemap when it is
     * split across an index.
     *
     * @return list<SitemapUrl>
     */
    public function page(int $offset, int $limit): array;
}

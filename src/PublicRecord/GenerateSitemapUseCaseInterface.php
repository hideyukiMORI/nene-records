<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

interface GenerateSitemapUseCaseInterface
{
    /**
     * Site-relative URLs for every published record in the current organization,
     * resolved through each type's permalink pattern.
     *
     * @return list<SitemapUrl>
     */
    public function execute(): array;
}

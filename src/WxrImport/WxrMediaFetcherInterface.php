<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

interface WxrMediaFetcherInterface
{
    /** Fetch a remote media file, or null if it cannot be retrieved. */
    public function fetch(string $url): ?WxrMediaFetchResult;
}

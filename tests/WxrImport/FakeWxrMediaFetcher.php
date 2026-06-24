<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\WxrImport;

use NeNeRecords\WxrImport\WxrMediaFetcherInterface;
use NeNeRecords\WxrImport\WxrMediaFetchResult;

final class FakeWxrMediaFetcher implements WxrMediaFetcherInterface
{
    /** @param array<string, WxrMediaFetchResult> $byUrl */
    public function __construct(private array $byUrl = [])
    {
    }

    public function fetch(string $url): ?WxrMediaFetchResult
    {
        return $this->byUrl[$url] ?? null;
    }
}

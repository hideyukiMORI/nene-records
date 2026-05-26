<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ProcessScheduledPublishOutput
{
    public function __construct(
        /** @var list<int> */
        public array $publishedIds,
    ) {
    }
}

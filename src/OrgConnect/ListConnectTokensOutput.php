<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

final readonly class ListConnectTokensOutput
{
    public function __construct(
        /** @var list<ConnectTokenSummary> */
        public array $items,
    ) {
    }
}

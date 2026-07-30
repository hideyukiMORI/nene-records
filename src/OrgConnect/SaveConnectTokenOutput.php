<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

final readonly class SaveConnectTokenOutput
{
    public function __construct(
        public ConnectTokenSummary $summary,
        /** False when an existing token was replaced — the caller answers 201 vs 200 with this. */
        public bool $created,
    ) {
    }
}

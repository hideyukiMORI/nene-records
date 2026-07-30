<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use SensitiveParameter;

final readonly class SaveConnectTokenInput
{
    /**
     * @param non-empty-string $token the raw token as issued by the other product
     */
    public function __construct(
        public ConnectTokenService $service,
        #[SensitiveParameter]
        public string $token,
    ) {
    }
}

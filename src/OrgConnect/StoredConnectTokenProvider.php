<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use NeNeRecords\Config\ConfigCipherInterface;

final readonly class StoredConnectTokenProvider implements ConnectTokenProviderInterface
{
    public function __construct(
        private ConnectTokenRepositoryInterface $tokens,
        private ConfigCipherInterface $cipher,
    ) {
    }

    public function secretFor(ConnectTokenService $service): ?string
    {
        $envelope = $this->tokens->findEnvelope($service);

        if ($envelope === null || $envelope === '') {
            return null;
        }

        return $this->cipher->decrypt($envelope);
    }
}

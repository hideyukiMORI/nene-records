<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

/**
 * Everything about an installed connect-token *except* the token.
 *
 * This type is the reason the admin API cannot leak the secret by accident: there is no
 * property to put it in. The read path (`findAllSummaries` / `findSummary`) never selects
 * `token_ciphertext`, so the value does not travel far enough to be forgotten about. Only
 * {@see ConnectTokenProviderInterface} can reach the plaintext, and nothing in the HTTP
 * layer depends on it.
 */
final readonly class ConnectTokenSummary
{
    public function __construct(
        public ConnectTokenService $service,
        /** Trailing characters of the token, for "which one is installed?" — never enough to use. */
        public string $hint,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}

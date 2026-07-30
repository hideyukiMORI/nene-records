<?php

declare(strict_types=1);

namespace NeNeRecords\OrgConnect;

use NeNeRecords\Config\ConfigDecryptException;
use NeNeRecords\Config\ConfigKeyException;

/**
 * The one seam that yields a connect-token in plaintext.
 *
 * Server-side callers only — the submission proxy (#1031) attaches the token to a
 * server-to-server request. Nothing in the HTTP response path may depend on this
 * interface; `ConnectTokenLeakPinTest` fails if that changes.
 */
interface ConnectTokenProviderInterface
{
    /**
     * @return non-empty-string|null null when no token is installed for the service
     *
     * @throws ConfigKeyException     when `NENE_RECORDS_CONFIG_KEY` is missing or unusable
     * @throws ConfigDecryptException when a token is installed but cannot be opened with the
     *                                configured key (typically a rotated key). Distinct from
     *                                null on purpose: "broken" and "absent" need different
     *                                operator actions.
     */
    public function secretFor(ConnectTokenService $service): ?string;
}

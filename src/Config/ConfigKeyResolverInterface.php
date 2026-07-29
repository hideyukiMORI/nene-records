<?php

declare(strict_types=1);

namespace NeNeRecords\Config;

/**
 * Supplies the raw 32-byte key used by {@see AesGcmConfigCipher}.
 */
interface ConfigKeyResolverInterface
{
    /**
     * @return non-empty-string raw key bytes (32)
     *
     * @throws ConfigKeyException when the key is absent, malformed, or the wrong length
     */
    public function resolve(): string;
}

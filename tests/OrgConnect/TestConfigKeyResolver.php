<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\OrgConnect;

use NeNeRecords\Config\ConfigKeyResolverInterface;

/**
 * A deterministic key so connect-token tests exercise the real cipher rather than a stub —
 * the encryption is the part most worth not faking.
 */
final readonly class TestConfigKeyResolver implements ConfigKeyResolverInterface
{
    private string $key;

    public function __construct(?string $key = null)
    {
        $this->key = $key ?? str_repeat("\x01", 32);
    }

    public function resolve(): string
    {
        /** @var non-empty-string */
        return $this->key;
    }
}

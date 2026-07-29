<?php

declare(strict_types=1);

namespace NeNeRecords\Config;

/**
 * Reads the configuration key from `NENE_RECORDS_CONFIG_KEY` (base64 of 32 raw bytes).
 *
 * Env-sourced and records-owned by decision (#1029): NENE2's `ConfigLoader` allow-list is
 * framework-owned, and `NENE_RECORDS_*` is already how records reads its own application
 * variables (`NENE_RECORDS_VITE_URL`). Keeping this key separate from
 * `NENE2_LOCAL_JWT_SECRET` means rotating the JWT secret does not make stored tokens
 * unreadable.
 *
 * Generate one with: `openssl rand -base64 32`
 */
final class EnvConfigKeyResolver implements ConfigKeyResolverInterface
{
    public const ENV_NAME = 'NENE_RECORDS_CONFIG_KEY';

    private const KEY_LENGTH = 32;

    public function resolve(): string
    {
        $configured = getenv(self::ENV_NAME);

        if (!is_string($configured) || trim($configured) === '') {
            throw new ConfigKeyException(
                self::ENV_NAME . ' is not set. Generate one with `openssl rand -base64 32`; '
                . 'without it records refuses to store or read connect-tokens.',
            );
        }

        $key = base64_decode(trim($configured), true);

        if ($key === false || strlen($key) !== self::KEY_LENGTH) {
            throw new ConfigKeyException(
                self::ENV_NAME . ' must be base64 of exactly ' . self::KEY_LENGTH . ' bytes '
                . '(`openssl rand -base64 32`).',
            );
        }

        return $key;
    }
}

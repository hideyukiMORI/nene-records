<?php

declare(strict_types=1);

namespace NeNeRecords\Config;

use RuntimeException;

/**
 * Raised when the configuration encryption key is absent or unusable.
 *
 * Deliberately fail-closed, mirroring {@see \Nene2\Auth\GuardedJwtSecretResolver}: without a
 * key records must refuse to *store* a secret as well as to read one back. Falling back to
 * plaintext would turn a missing environment variable into a silent downgrade, which is the
 * failure mode this whole design exists to prevent.
 */
final class ConfigKeyException extends RuntimeException
{
}

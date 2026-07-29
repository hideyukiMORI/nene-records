<?php

declare(strict_types=1);

namespace NeNeRecords\Config;

use RuntimeException;

/**
 * Raised when an envelope cannot be opened with the configured key.
 *
 * The common cause is not corruption but a *rotated key*: the initial implementation ships no
 * re-encryption path (YAGNI), so changing `NENE_RECORDS_CONFIG_KEY` makes every stored value
 * unreadable and the operator re-pastes the token. Callers must surface this rather than
 * treat it as "no token configured" — the two states need different operator actions.
 */
final class ConfigDecryptException extends RuntimeException
{
}

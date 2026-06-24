<?php

declare(strict_types=1);

namespace NeNeRecords\WxrImport;

use RuntimeException;

/** Thrown when a WXR payload is not well-formed XML or not a recognizable export. */
final class WxrParseException extends RuntimeException
{
}

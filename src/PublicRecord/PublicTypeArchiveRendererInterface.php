<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders a resolved type archive as crawlable, SPA-hydratable HTML.
 *
 * Split from {@see RenderPublicTypeArchiveHandler} so the edge layer (which owns the
 * "should this path even be an archive?" decision) can be unit-tested against a
 * lightweight fake instead of the template stack (#877).
 */
interface PublicTypeArchiveRendererInterface
{
    public function render(GetPublicTypeArchiveOutput $archive, ServerRequestInterface $request): ResponseInterface;
}

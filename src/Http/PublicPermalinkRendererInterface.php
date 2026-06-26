<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves an incoming public request path to the record whose stored custom
 * permalink matches it (#651) and renders that record's crawlable view.
 *
 * Defined in Http (the consumer side) so the single-origin edge layer can depend
 * on it without reaching into the PublicRecord rendering internals; the concrete
 * implementation lives in PublicRecord.
 */
interface PublicPermalinkRendererInterface
{
    /**
     * Render the public view of the record whose custom permalink equals the
     * normalized request path, or null when no publicly-visible record claims it
     * (so the caller falls through to type-based routing or the SPA shell).
     */
    public function renderByPermalink(string $path, ServerRequestInterface $request): ?ResponseInterface;
}

<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * `GET /robots.txt` — crawler directives plus an absolute `Sitemap:` pointer,
 * built from the request's scheme+host (single-origin reverse proxy aware).
 */
final readonly class RenderRobotsHandler
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        $sitemapUrl = $uri->getScheme() . '://' . $uri->getAuthority() . '/sitemap.xml';

        $body = RobotsTxtRenderer::render($sitemapUrl);

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withBody($this->streamFactory->createStream($body));
    }
}

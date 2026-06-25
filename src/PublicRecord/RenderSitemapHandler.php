<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * `GET /sitemap.xml` — the per-organization XML sitemap. The home page plus every
 * published record permalink, with absolute URLs built from the request's
 * scheme+host (so it works behind the single-origin reverse proxy).
 */
final readonly class RenderSitemapHandler
{
    public function __construct(
        private GenerateSitemapUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority();

        $urls = array_merge([new SitemapUrl('/')], $this->useCase->execute());
        $xml = SitemapXmlRenderer::render($baseUrl, $urls);

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->withBody($this->streamFactory->createStream($xml));
    }
}

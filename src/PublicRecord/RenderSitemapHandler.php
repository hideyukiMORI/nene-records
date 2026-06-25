<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * `GET /sitemap.xml` — the per-organization XML sitemap.
 *
 * The global URL list is the home page plus every published record permalink.
 * While it fits in one file (≤ chunk size) a single `<urlset>` is served. Beyond
 * that the same URL returns a `<sitemapindex>` pointing at child sitemaps served
 * from `/sitemap.xml?page=N` — keeping each file under the sitemap protocol's
 * 50,000-URL limit. Absolute URLs are built from the request scheme+host so it
 * works behind the single-origin reverse proxy.
 */
final readonly class RenderSitemapHandler
{
    /** Under the protocol's 50,000 cap, with headroom for the home URL in page 1. */
    public const DEFAULT_CHUNK_SIZE = 45000;

    private int $chunkSize;

    public function __construct(
        private GenerateSitemapUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        ?int $chunkSize = null,
        /** Sub-directory install prefix (`APP_BASE_PATH`); '' = served at root. */
        private string $basePath = '',
    ) {
        $this->chunkSize = $chunkSize !== null && $chunkSize > 0 ? $chunkSize : self::DEFAULT_CHUNK_SIZE;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        // The renderer joins paths onto this; folding the base path in keeps every
        // <loc> / child-sitemap URL under the sub-directory install (#zip-install)
        // and the per-request tenant prefix in directory mode (nene2.base_prefix).
        $baseUrl = $uri->getScheme() . '://' . $uri->getAuthority()
            . $this->basePath . (string) $request->getAttribute('nene2.base_prefix', '');

        // Global list = home page (1) + every published record.
        $totalUrls = 1 + $this->useCase->count();
        $chunks = (int) max(1, (int) ceil($totalUrls / $this->chunkSize));

        $page = $this->requestedPage($request);

        if ($page !== null) {
            if ($page < 1 || $page > $chunks) {
                return $this->responseFactory->createResponse(404);
            }

            return $this->xml(SitemapXmlRenderer::render($baseUrl, $this->chunkUrls($page - 1)));
        }

        // No page param: a single urlset when everything fits, otherwise an index.
        if ($chunks === 1) {
            return $this->xml(SitemapXmlRenderer::render($baseUrl, $this->chunkUrls(0)));
        }

        $children = [];
        for ($n = 1; $n <= $chunks; $n++) {
            $children[] = '/sitemap.xml?page=' . $n;
        }

        return $this->xml(SitemapXmlRenderer::renderIndex($baseUrl, $children));
    }

    /**
     * The home page lives in chunk 0; record offsets shift by one to make room.
     *
     * @return list<SitemapUrl>
     */
    private function chunkUrls(int $chunkIndex): array
    {
        if ($chunkIndex === 0) {
            return array_merge([new SitemapUrl('/')], $this->useCase->page(0, $this->chunkSize - 1));
        }

        return $this->useCase->page($chunkIndex * $this->chunkSize - 1, $this->chunkSize);
    }

    private function requestedPage(ServerRequestInterface $request): ?int
    {
        $raw = $request->getQueryParams()['page'] ?? null;

        if ($raw === null) {
            return null;
        }

        return is_string($raw) && ctype_digit($raw) ? (int) $raw : 0;
    }

    private function xml(string $body): ResponseInterface
    {
        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->withBody($this->streamFactory->createStream($body));
    }
}

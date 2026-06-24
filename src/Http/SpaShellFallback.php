<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Single-origin SPA fallback: when the router 404s a GET HTML navigation to a
 * non-API path, serve the built SPA shell (`frontend/dist/index.html`) so the
 * client router can handle `/admin`, `/login`, `/search`, `/tag/:slug`, browse
 * pages, etc. API / media / view / asset paths keep their genuine 404, and the
 * fallback is a no-op when no build is present (dev / unbuilt).
 */
final readonly class SpaShellFallback
{
    private const PASSTHROUGH = '#^/(api|media|view|assets)(/|$)#';

    public function __construct(
        private string $shellPath,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        if (strtoupper($request->getMethod()) !== 'GET') {
            return $response;
        }

        if (!str_contains($request->getHeaderLine('Accept'), 'text/html')) {
            return $response;
        }

        if (preg_match(self::PASSTHROUGH, $request->getUri()->getPath()) === 1) {
            return $response;
        }

        if (!is_file($this->shellPath)) {
            return $response;
        }

        $html = file_get_contents($this->shellPath);

        if ($html === false) {
            return $response;
        }

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream($html));
    }
}

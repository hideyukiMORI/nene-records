<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class ServeMediaHandler
{
    public function __construct(
        private StorageInterface $storage,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $year = (string) ($parameters['year'] ?? '');
        $month = (string) ($parameters['month'] ?? '');
        $filename = (string) ($parameters['filename'] ?? '');

        // Prevent directory traversal
        if (
            $year === '' || $month === '' || $filename === ''
            || str_contains($year, '.') || str_contains($month, '.')
            || str_contains($filename, '/')  || str_contains($filename, '\\')
            || str_contains($filename, '..')
        ) {
            return $this->responseFactory->createResponse(404);
        }

        $key = $year . '/' . $month . '/' . $filename;

        if (!$this->storage->exists($key)) {
            return $this->responseFactory->createResponse(404);
        }

        $stream = $this->streamFactory->createStreamFromResource($this->storage->readStream($key));
        $isSvg = str_ends_with(strtolower($key), '.svg');

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', $isSvg ? 'image/svg+xml' : $this->storage->mimeType($key))
            ->withHeader('Content-Length', (string) $this->storage->size($key))
            ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
            // Never let the browser sniff a different (executable) type.
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($stream);

        if ($isSvg) {
            // Defence in depth: uploads are already deep-sanitised, but if an SVG
            // is opened top-level / via <object>, this CSP blocks any script from
            // running in the app origin. <img>/CSS embedding is unaffected.
            $response = $response->withHeader(
                'Content-Security-Policy',
                "default-src 'none'; style-src 'unsafe-inline'",
            );
        }

        return $response;
    }
}

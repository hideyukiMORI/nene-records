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
        private string $storageRoot,
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

        $path = $this->storageRoot . '/' . $year . '/' . $month . '/' . $filename;

        if (!is_file($path)) {
            return $this->responseFactory->createResponse(404);
        }

        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $stream = $this->streamFactory->createStreamFromFile($path);

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->withBody($stream);
    }
}

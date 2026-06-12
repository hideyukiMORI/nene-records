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

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', $this->storage->mimeType($key))
            ->withHeader('Content-Length', (string) $this->storage->size($key))
            ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->withBody($stream);
    }
}

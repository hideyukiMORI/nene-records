<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * On-demand image derivatives: GET /media/{preset}/{year}/{month}/{filename}.
 *
 * Generates the requested preset on the first request, caches it under a
 * "derivatives/" prefix in storage, and serves the cached object thereafter.
 * Output format is negotiated (?fm= override, then Accept: image/avif|webp,
 * else the source format). Presets are whitelisted to bound generation cost.
 */
final readonly class ServeDerivativeHandler
{
    public function __construct(
        private StorageInterface $storage,
        private ImageProcessorInterface $processor,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $preset = (string) ($parameters['preset'] ?? '');
        $year = (string) ($parameters['year'] ?? '');
        $month = (string) ($parameters['month'] ?? '');
        $filename = (string) ($parameters['filename'] ?? '');

        if (!MediaImagePresets::isValid($preset) || $this->hasTraversal($year, $month, $filename)) {
            return $this->responseFactory->createResponse(404);
        }

        $originalKey = $year . '/' . $month . '/' . $filename;

        if (!$this->storage->exists($originalKey)) {
            return $this->responseFactory->createResponse(404);
        }

        $sourceMime = $this->storage->mimeType($originalKey);
        if (!$this->processor->supportsSource($sourceMime)) {
            return $this->responseFactory->createResponse(404);
        }

        $fm = $request->getQueryParams()['fm'] ?? null;
        $format = $this->negotiateFormat(is_string($fm) ? $fm : null, $request->getHeaderLine('Accept'), $sourceMime);
        [$mime, $ext] = $this->formatMeta($format);

        $base = pathinfo($filename, PATHINFO_FILENAME);
        $derivativeKey = "derivatives/{$preset}/{$format}/{$year}/{$month}/{$base}.{$ext}";
        $etag = '"' . md5($derivativeKey) . '"';

        if (!$this->storage->exists($derivativeKey)) {
            try {
                $original = stream_get_contents($this->storage->readStream($originalKey));
                $derived = $this->processor->resize(
                    $original === false ? '' : $original,
                    MediaImagePresets::maxWidth($preset),
                    $format,
                );
            } catch (\RuntimeException) {
                // Source could not be decoded/encoded (corrupt or unsupported payload).
                return $this->responseFactory->createResponse(404);
            }

            try {
                $this->storage->write($derivativeKey, $derived);
            } catch (\RuntimeException $e) {
                // Only the CACHE write failed (unwritable var/media — permission or
                // disk trouble); the derivative itself was generated fine. Serve it
                // from memory instead of failing the image, and log so the broken
                // cache directory is visible to operators (#949: this used to leak
                // a PHP warning into a 200/text/html body and no log at all).
                $this->logger->warning('media: derivative cache write failed, serving uncached', [
                    'derivative_key' => $derivativeKey,
                    'error' => $e->getMessage(),
                ]);

                return $this->responseFactory->createResponse(200)
                    ->withHeader('Content-Type', $mime)
                    ->withHeader('Content-Length', (string) strlen($derived))
                    ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
                    ->withHeader('ETag', $etag)
                    ->withBody($this->streamFactory->createStream($derived));
            }
        }

        if (trim($request->getHeaderLine('If-None-Match')) === $etag) {
            return $this->responseFactory->createResponse(304)->withHeader('ETag', $etag);
        }

        $stream = $this->streamFactory->createStreamFromResource($this->storage->readStream($derivativeKey));

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', $mime)
            ->withHeader('Content-Length', (string) $this->storage->size($derivativeKey))
            ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->withHeader('ETag', $etag)
            ->withBody($stream);
    }

    private function hasTraversal(string $year, string $month, string $filename): bool
    {
        return $year === '' || $month === '' || $filename === ''
            || str_contains($year, '.') || str_contains($month, '.')
            || str_contains($filename, '/') || str_contains($filename, '\\')
            || str_contains($filename, '..');
    }

    private function negotiateFormat(?string $fm, string $accept, string $sourceMime): string
    {
        $allowed = [
            ImageProcessorInterface::FORMAT_WEBP,
            ImageProcessorInterface::FORMAT_AVIF,
            ImageProcessorInterface::FORMAT_JPEG,
            ImageProcessorInterface::FORMAT_PNG,
        ];

        // encoder はビルドフラグ依存（GD の AVIF 無しビルド等）。processor が出力
        // できない形式を選ぶと encode 時に fatal になるため、交渉は常に能力込みで行う。
        if ($fm !== null && in_array($fm, $allowed, true) && $this->processor->supportsOutput($fm)) {
            return $fm;
        }

        if (str_contains($accept, 'image/avif') && $this->processor->supportsOutput(ImageProcessorInterface::FORMAT_AVIF)) {
            return ImageProcessorInterface::FORMAT_AVIF;
        }

        if (str_contains($accept, 'image/webp') && $this->processor->supportsOutput(ImageProcessorInterface::FORMAT_WEBP)) {
            return ImageProcessorInterface::FORMAT_WEBP;
        }

        $sourceFormat = match ($sourceMime) {
            'image/png' => ImageProcessorInterface::FORMAT_PNG,
            'image/webp' => ImageProcessorInterface::FORMAT_WEBP,
            'image/avif' => ImageProcessorInterface::FORMAT_AVIF,
            default => ImageProcessorInterface::FORMAT_JPEG,
        };

        if ($this->processor->supportsOutput($sourceFormat)) {
            return $sourceFormat;
        }

        return $this->processor->supportsOutput(ImageProcessorInterface::FORMAT_PNG)
            ? ImageProcessorInterface::FORMAT_PNG
            : ImageProcessorInterface::FORMAT_JPEG;
    }

    /** @return array{0: string, 1: string} [mime, extension] */
    private function formatMeta(string $format): array
    {
        return match ($format) {
            ImageProcessorInterface::FORMAT_WEBP => ['image/webp', 'webp'],
            ImageProcessorInterface::FORMAT_AVIF => ['image/avif', 'avif'],
            ImageProcessorInterface::FORMAT_PNG => ['image/png', 'png'],
            default => ['image/jpeg', 'jpg'],
        };
    }
}

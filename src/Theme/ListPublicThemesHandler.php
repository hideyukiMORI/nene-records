<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\ConditionalGetHelper;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public read of runtime themes — the public site fetches this to apply a
 * runtime (data-driven) active theme as a scoped stylesheet (no rebuild).
 * Open via ALWAYS_OPEN_PREFIXES (`/api/v1/public/`). Revalidates each request
 * (cheap 304 via ETag) so admin theme edits reflect promptly. Mirrors
 * ListPublicMenusHandler.
 */
final readonly class ListPublicThemesHandler
{
    private const CACHE_CONTROL = 'public, max-age=0, must-revalidate';

    public function __construct(
        private ListThemesUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        $data = [
            'items' => array_map(
                static fn (Theme $theme) => ThemeHttpMapper::toArray($theme),
                $output->items,
            ),
        ];

        $etag = '"' . md5(json_encode($data, JSON_THROW_ON_ERROR)) . '"';

        $notModified = ConditionalGetHelper::check($request, $this->responseFactory, $etag);
        if ($notModified !== null) {
            return $notModified->withHeader('Cache-Control', self::CACHE_CONTROL);
        }

        return $this->response->create($data, 200, [
            'Cache-Control' => self::CACHE_CONTROL,
            'ETag' => $etag,
        ]);
    }
}

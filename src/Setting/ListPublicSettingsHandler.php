<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use Nene2\Http\ConditionalGetHelper;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListPublicSettingsHandler
{
    // Settings drive the public site's identity and active theme, so an admin
    // change must reflect promptly. `max-age=0, must-revalidate` keeps the
    // response cacheable but forces an ETag revalidation on every request — a
    // cheap 304 when unchanged, immediate pickup when it changes.
    private const CACHE_CONTROL = 'public, max-age=0, must-revalidate';

    public function __construct(
        private ListPublicSettingsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        $data = [
            'items' => array_map(
                static fn (SettingEntry $entry) => SettingHttpMapper::entryToPublicArray($entry),
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

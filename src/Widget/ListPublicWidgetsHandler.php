<?php

declare(strict_types=1);

namespace NeNeRecords\Widget;

use Nene2\Http\ConditionalGetHelper;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListPublicWidgetsHandler
{
    private const CACHE_CONTROL = 'public, max-age=300, stale-while-revalidate=3600';

    public function __construct(
        private ListWidgetsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        $data = [
            'items' => array_map(
                static fn (Widget $item) => WidgetHttpMapper::toArray($item),
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

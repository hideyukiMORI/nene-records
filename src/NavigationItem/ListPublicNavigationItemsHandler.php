<?php

declare(strict_types=1);

namespace NeNeRecords\NavigationItem;

use Nene2\Http\ConditionalGetHelper;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListPublicNavigationItemsHandler
{
    private const CACHE_CONTROL = 'public, max-age=300, stale-while-revalidate=3600';

    public function __construct(
        private ListNavigationItemsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        $items = $output->items;

        // Optional ?location=header|footer|side narrows the menu for a region.
        $queryParams = $request->getQueryParams();
        $location = isset($queryParams['location']) ? trim((string) $queryParams['location']) : '';
        if ($location !== '' && NavLocations::isValid($location)) {
            $items = array_values(array_filter(
                $items,
                static fn (NavigationItem $item) => $item->location === $location,
            ));
        }

        $data = [
            'items' => array_map(
                static fn (NavigationItem $item) => NavigationItemHttpMapper::toArray($item),
                $items,
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

<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListMenusHandler
{
    public function __construct(
        private ListMenusUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        return $this->response->create([
            'items' => array_map(
                static fn (Menu $menu) => MenuHttpMapper::toArray($menu),
                $output->items,
            ),
        ]);
    }
}

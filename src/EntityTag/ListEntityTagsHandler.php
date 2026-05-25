<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListEntityTagsHandler
{
    public function __construct(
        private ListEntityTagsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($parameters['entityId'] ?? 0);

        if ($entityId <= 0) {
            throw new EntityNotFoundException($entityId);
        }

        $output = $this->useCase->execute(new ListEntityTagsInput($entityId));

        return $this->response->create([
            'items' => array_map(
                static fn (ListEntityTagItem $item) => [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'name' => $item->name,
                ],
                $output->items,
            ),
        ]);
    }
}

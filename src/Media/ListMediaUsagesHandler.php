<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListMediaUsagesHandler
{
    public function __construct(
        private FindMediaUsagesUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new MediaNotFoundException($id);
        }

        $usages = $this->useCase->execute($id);

        $items = array_map(
            static fn (MediaUsage $usage): array => [
                'entity_id' => $usage->entityId,
                'entity_type_slug' => $usage->entityTypeSlug,
                'entity_slug' => $usage->entitySlug,
                'status' => $usage->status,
                'field_key' => $usage->fieldKey,
                'title' => $usage->title,
            ],
            $usages,
        );

        return $this->response->create(['items' => $items]);
    }
}

<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListEntitiesHandler
{
    public function __construct(
        private ListEntitiesUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListEntitiesInput($pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListEntityItem $item) => [
                        'id' => $item->id,
                        'entity_type_id' => $item->entityTypeId,
                        'is_deleted' => $item->isDeleted,
                        'deleted_at' => $item->deletedAtIso,
                    ],
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}

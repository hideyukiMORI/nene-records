<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Nene2\Http\QueryStringParser;
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

        $tagsFromTags = QueryStringParser::commaSeparated($request, 'tags') ?? [];
        $tagsFromTag = QueryStringParser::commaSeparated($request, 'tag') ?? [];
        $tagSlugs = array_values(array_unique([...$tagsFromTags, ...$tagsFromTag]));

        $entityTypeId = QueryStringParser::int($request, 'entity_type_id');
        $relationFilters = EntityRelationQueryParser::parseRelationFilters($request->getQueryParams());

        $rawStatus = $request->getQueryParams()['status'] ?? null;
        $status = is_string($rawStatus) && EntityStatus::isValid($rawStatus) ? $rawStatus : null;

        $output = $this->useCase->execute(new ListEntitiesInput(
            limit: $pagination->limit,
            offset: $pagination->offset,
            criteria: new EntityListCriteria(
                entityTypeId: $entityTypeId,
                tagSlugs: $tagSlugs,
                relationFilters: $relationFilters,
                status: $status,
            ),
        ));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListEntityItem $item) => [
                        'id' => $item->id,
                        'entity_type_id' => $item->entityTypeId,
                        'status' => $item->status,
                        'published_at' => $item->publishedAtIso,
                        'is_deleted' => $item->isDeleted,
                        'deleted_at' => $item->deletedAtIso,
                    ],
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
                total: $output->total,
            ))->toArray(),
        );
    }
}

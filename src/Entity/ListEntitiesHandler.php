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
        $status = is_string($rawStatus) ? EntityStatus::tryFrom($rawStatus) : null;

        $rawQ = $request->getQueryParams()['q'] ?? null;
        $q = (is_string($rawQ) && $rawQ !== '') ? $rawQ : null;

        $rawSort = $request->getQueryParams()['sort'] ?? null;
        $sortKey = is_string($rawSort) ? (EntitySortKey::tryFrom($rawSort) ?? EntitySortKey::Id) : EntitySortKey::Id;

        $rawOrder = $request->getQueryParams()['order'] ?? null;
        $sortOrder = is_string($rawOrder) ? (EntitySortOrder::tryFrom($rawOrder) ?? EntitySortOrder::Desc) : EntitySortOrder::Desc;

        $output = $this->useCase->execute(new ListEntitiesInput(
            limit: $pagination->limit,
            offset: $pagination->offset,
            criteria: new EntityListCriteria(
                entityTypeId: $entityTypeId,
                tagSlugs: $tagSlugs,
                relationFilters: $relationFilters,
                status: $status,
                q: $q,
                sortKey: $sortKey,
                sortOrder: $sortOrder,
            ),
        ));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListEntityItem $item) => [
                        'id' => $item->id,
                        'entity_type_id' => $item->entityTypeId,
                        'slug' => $item->slug,
                        'status' => $item->status,
                        'published_at' => $item->publishedAtIso,
                        'scheduled_at' => $item->scheduledAtIso,
                        'is_deleted' => $item->isDeleted,
                        'deleted_at' => $item->deletedAtIso,
                        'created_at' => $item->createdAtIso,
                        'updated_at' => $item->updatedAtIso,
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

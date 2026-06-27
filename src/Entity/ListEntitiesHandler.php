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
        private ExcerptResolver $excerpts,
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

        $publishedFrom = $this->parseDate($request->getQueryParams()['published_from'] ?? null);
        // `published_to` is an inclusive day; store the exclusive next-day bound so
        // ISO timestamps on the last day still match (`published_at < to+1d`).
        $publishedTo = $this->parseDate($request->getQueryParams()['published_to'] ?? null);
        $publishedToExclusive = $publishedTo === null
            ? null
            : (new \DateTimeImmutable($publishedTo))->modify('+1 day')->format('Y-m-d');

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
                publishedFrom: $publishedFrom,
                publishedToExclusive: $publishedToExclusive,
            ),
        ));

        // `?include=excerpt` adds a server-computed teaser (used by the public
        // feed and post-list widgets). Off by default so the admin list stays lean.
        $include = $request->getQueryParams()['include'] ?? '';
        $wantExcerpt = is_string($include) && in_array('excerpt', explode(',', $include), true);
        $excerptByEntity = $wantExcerpt ? $this->excerpts->resolve($output->items) : [];

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    function (ListEntityItem $item) use ($wantExcerpt, $excerptByEntity) {
                        $row = [
                            'id' => $item->id,
                            'entity_type_id' => $item->entityTypeId,
                            'slug' => $item->slug,
                            'permalink' => $item->permalink,
                            'status' => $item->status,
                            'published_at' => $item->publishedAtIso,
                            'scheduled_at' => $item->scheduledAtIso,
                            'is_deleted' => $item->isDeleted,
                            'deleted_at' => $item->deletedAtIso,
                            'created_at' => $item->createdAtIso,
                            'updated_at' => $item->updatedAtIso,
                            'meta_title' => $item->metaTitle,
                            'meta_description' => $item->metaDescription,
                        ];
                        if ($wantExcerpt) {
                            $row['excerpt'] = $excerptByEntity[$item->id] ?? '';
                        }

                        return $row;
                    },
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
                total: $output->total,
            ))->toArray(),
        );
    }

    /** Returns a valid `Y-m-d` string, or null for anything else. */
    private function parseDate(mixed $raw): ?string
    {
        if (!is_string($raw) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) !== 1) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw);

        return $date !== false && $date->format('Y-m-d') === $raw ? $raw : null;
    }
}

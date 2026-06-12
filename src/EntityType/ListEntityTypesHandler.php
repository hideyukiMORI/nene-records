<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListEntityTypesHandler
{
    public function __construct(
        private ListEntityTypesUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListEntityTypesInput($pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListEntityTypeItem $item) => [
                        'id'                          => $item->id,
                        'name'                        => $item->name,
                        'slug'                        => $item->slug,
                        'is_pinned'                   => $item->isPinned,
                        'labels'                      => $item->labels ?? new \stdClass(),
                        'permalink_pattern'           => $item->permalinkPattern,
                        'previous_permalink_pattern'  => $item->previousPermalinkPattern,
                        'display_order'               => $item->displayOrder,
                        'default_layout'              => $item->defaultLayout,
                    ],
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}

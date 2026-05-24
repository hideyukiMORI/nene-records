<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListIntFieldsHandler
{
    public function __construct(
        private ListIntFieldsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListIntFieldsInput($pagination->limit, $pagination->offset));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListIntFieldItem $item) => [
                        'id'        => $item->id,
                        'entity_id' => $item->entityId,
                        'field_key' => $item->fieldKey,
                        'value'     => $item->value,
                    ],
                    $output->items,
                ),
                limit:  $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}

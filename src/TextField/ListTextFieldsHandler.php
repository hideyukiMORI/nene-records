<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListTextFieldsHandler
{
    public function __construct(
        private ListTextFieldsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);
        $query = $request->getQueryParams();

        $entityId = null;
        if (isset($query['entity_id'])) {
            $raw = (int) $query['entity_id'];
            if ($raw > 0) {
                $entityId = $raw;
            }
        }

        $entityTypeId = null;
        if ($entityId === null && isset($query['entity_type_id'])) {
            $raw = (int) $query['entity_type_id'];
            if ($raw > 0) {
                $entityTypeId = $raw;
            }
        }

        $output = $this->useCase->execute(new ListTextFieldsInput(
            entityId: $entityId,
            entityTypeId: $entityTypeId,
            limit: $pagination->limit,
            offset: $pagination->offset,
        ));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListTextFieldItem $item) => [
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

<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListFieldDefsHandler
{
    public function __construct(
        private ListFieldDefsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pagination = PaginationQueryParser::parse($request);
        $query = $request->getQueryParams();

        $entityTypeId = null;
        if (isset($query['entity_type_id'])) {
            $raw = (int) $query['entity_type_id'];
            if ($raw > 0) {
                $entityTypeId = $raw;
            }
        }

        $output = $this->useCase->execute(new ListFieldDefsInput(
            entityTypeId: $entityTypeId,
            limit: $pagination->limit,
            offset: $pagination->offset,
        ));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListFieldDefItem $item) => [
                        'id' => $item->id,
                        'entity_type_id' => $item->entityTypeId,
                        'field_key' => $item->fieldKey,
                        'data_type' => $item->dataType,
                    ],
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}

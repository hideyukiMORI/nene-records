<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListBlocksFieldsHandler
{
    public function __construct(
        private ListBlocksFieldsUseCaseInterface $useCase,
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

        $locale = null;
        if (isset($query['locale']) && is_string($query['locale']) && $query['locale'] !== '') {
            $locale = $query['locale'];
        }

        $output = $this->useCase->execute(new ListBlocksFieldsInput(
            entityId: $entityId,
            entityTypeId: $entityTypeId,
            limit: $pagination->limit,
            offset: $pagination->offset,
            locale: $locale,
        ));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (ListBlocksFieldItem $item) => [
                        'id'        => $item->id,
                        'entity_id' => $item->entityId,
                        'field_key' => $item->fieldKey,
                        'value'     => $item->value,
                        'locale'    => $item->locale,
                    ],
                    $output->items,
                ),
                limit:  $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}

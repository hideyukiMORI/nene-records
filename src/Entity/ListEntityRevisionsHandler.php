<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonResponseFactory;
use Nene2\Http\PaginationQueryParser;
use Nene2\Http\PaginationResponse;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListEntityRevisionsHandler
{
    public function __construct(
        private ListEntityRevisionsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityNotFoundException($id);
        }

        $pagination = PaginationQueryParser::parse($request);

        $output = $this->useCase->execute(new ListEntityRevisionsInput(
            entityId: $id,
            limit: $pagination->limit,
            offset: $pagination->offset,
        ));

        return $this->response->create(
            (new PaginationResponse(
                items: array_map(
                    static fn (EntityRevision $revision) => EntityHttpMapper::revisionToArray($revision),
                    $output->items,
                ),
                limit: $output->limit,
                offset: $output->offset,
            ))->toArray(),
        );
    }
}

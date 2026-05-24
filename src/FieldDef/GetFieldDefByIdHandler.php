<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetFieldDefByIdHandler
{
    public function __construct(
        private GetFieldDefByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new FieldDefNotFoundException($id);
        }

        $output = $this->useCase->execute(new GetFieldDefByIdInput($id));

        return $this->response->create([
            'id' => $output->id,
            'entity_type_id' => $output->entityTypeId,
            'field_key' => $output->fieldKey,
            'data_type' => $output->dataType,
        ]);
    }
}

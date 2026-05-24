<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetDateTimeFieldByIdHandler
{
    public function __construct(
        private GetDateTimeFieldByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new DateTimeFieldNotFoundException($id);
        }

        $output = $this->useCase->execute(new GetDateTimeFieldByIdInput($id));

        return $this->response->create([
            'id'        => $output->id,
            'entity_id' => $output->entityId,
            'field_key' => $output->fieldKey,
            'value'     => $output->value,
        ]);
    }
}

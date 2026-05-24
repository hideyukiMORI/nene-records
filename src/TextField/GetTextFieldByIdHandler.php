<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetTextFieldByIdHandler
{
    public function __construct(
        private GetTextFieldByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new TextFieldNotFoundException($id);
        }

        $output = $this->useCase->execute(new GetTextFieldByIdInput($id));

        return $this->response->create([
            'id'        => $output->id,
            'entity_id' => $output->entityId,
            'field_key' => $output->fieldKey,
            'value'     => $output->value,
        ]);
    }
}

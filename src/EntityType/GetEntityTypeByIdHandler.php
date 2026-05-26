<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetEntityTypeByIdHandler
{
    public function __construct(
        private GetEntityTypeByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityTypeNotFoundException($id);
        }

        $output = $this->useCase->execute(new GetEntityTypeByIdInput($id));

        return $this->response->create([
            'id'                => $output->id,
            'name'              => $output->name,
            'slug'              => $output->slug,
            'is_pinned'         => $output->isPinned,
            'labels'            => $output->labels ?? new \stdClass(),
            'permalink_pattern' => $output->permalinkPattern,
        ]);
    }
}

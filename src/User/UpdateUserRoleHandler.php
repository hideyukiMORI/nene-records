<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateUserRoleHandler
{
    public function __construct(
        private UpdateUserRoleUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];
        $role = trim((string) ($body['role'] ?? ''));

        if ($role === '') {
            $errors[] = new ValidationError('role', 'Role is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new UpdateUserRoleInput(id: $id, role: $role));

        return $this->response->create([
            'id' => $output->id,
            'email' => $output->email,
            'role' => $output->role,
            'status' => $output->status,
        ]);
    }
}

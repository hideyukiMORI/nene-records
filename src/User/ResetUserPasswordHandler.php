<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ResetUserPasswordHandler
{
    public function __construct(
        private ResetUserPasswordUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];
        $newPassword = (string) ($body['new_password'] ?? '');

        if ($newPassword === '') {
            $errors[] = new ValidationError('new_password', 'New password is required.', 'required');
        } elseif (strlen($newPassword) < 8) {
            $errors[] = new ValidationError('new_password', 'Password must be at least 8 characters.', 'min_length');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->useCase->execute(new ResetUserPasswordInput(id: $id, newPassword: $newPassword));

        return $this->responseFactory->createResponse(204);
    }
}

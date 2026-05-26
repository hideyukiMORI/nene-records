<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AcceptInviteHandler
{
    public function __construct(
        private AcceptInviteUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];
        $token = trim((string) ($body['token'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($token === '') {
            $errors[] = new ValidationError('token', 'Token is required.', 'required');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'Password is required.', 'required');
        } elseif (strlen($password) < 8) {
            $errors[] = new ValidationError('password', 'Password must be at least 8 characters.', 'min_length');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->useCase->execute(new AcceptInviteInput(token: $token, password: $password));

        return $this->responseFactory->createResponse(204);
    }
}

<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConfirmPasswordResetHandler
{
    public function __construct(
        private ConfirmPasswordResetUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];
        $token = trim((string) ($body['token'] ?? ''));
        $newPassword = (string) ($body['new_password'] ?? '');

        if ($token === '') {
            $errors[] = new ValidationError('token', 'Token is required.', 'required');
        }

        if ($newPassword === '') {
            $errors[] = new ValidationError('new_password', 'New password is required.', 'required');
        } elseif (strlen($newPassword) < 8) {
            $errors[] = new ValidationError('new_password', 'Password must be at least 8 characters.', 'min_length');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->useCase->execute(new ConfirmPasswordResetInput(token: $token, newPassword: $newPassword));

        return $this->responseFactory->createResponse(204);
    }
}

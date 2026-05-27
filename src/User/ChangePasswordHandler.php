<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ChangePasswordHandler
{
    public function __construct(
        private ChangePasswordUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $claims = $request->getAttribute('nene2.auth.claims');
        $currentUserEmail = is_array($claims) ? (string) ($claims['sub'] ?? '') : '';

        $body = JsonRequestBodyParser::parse($request);

        $errors = [];
        $currentPassword = (string) ($body['current_password'] ?? '');
        $newPassword = (string) ($body['new_password'] ?? '');

        if ($currentPassword === '') {
            $errors[] = new ValidationError('current_password', 'Current password is required.', 'required');
        }

        if ($newPassword === '') {
            $errors[] = new ValidationError('new_password', 'New password is required.', 'required');
        } elseif (strlen($newPassword) < 8) {
            $errors[] = new ValidationError('new_password', 'Password must be at least 8 characters.', 'min_length');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->useCase->execute(new ChangePasswordInput(
            currentUserEmail: $currentUserEmail,
            currentPassword: $currentPassword,
            newPassword: $newPassword,
        ));

        return $this->responseFactory->createResponse(204);
    }
}

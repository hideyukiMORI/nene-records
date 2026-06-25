<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `POST /api/v1/auth/confirm-email` — confirm a signup email from its token.
 * Public (under the always-open `/api/v1/auth/` prefix); invalid/expired tokens
 * surface via EmailVerificationTokenException → 400/410.
 */
final readonly class ConfirmEmailHandler
{
    public function __construct(
        private ConfirmEmailUseCase $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);
        $token = isset($body['token']) && is_string($body['token']) ? trim($body['token']) : '';

        if ($token === '') {
            throw new ValidationException([new ValidationError('token', 'Token is required.', 'required')]);
        }

        $this->useCase->execute($token);

        return $this->response->create(['verified' => true], 200);
    }
}

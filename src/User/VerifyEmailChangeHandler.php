<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public endpoint hit when a user clicks the verification link sent to their new address.
 * The token may arrive as a `?token=` query parameter (the email link form) or in the
 * JSON body. The body is parsed leniently so a body-less query-param request is still valid.
 */
final readonly class VerifyEmailChangeHandler
{
    public function __construct(
        private VerifyEmailChangeUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->extractToken($request);

        if ($token === '') {
            throw new ValidationException([
                new ValidationError('token', 'Token is required.', 'required'),
            ]);
        }

        $this->useCase->execute(new VerifyEmailChangeInput(token: $token));

        return $this->responseFactory->createResponse(204);
    }

    private function extractToken(ServerRequestInterface $request): string
    {
        $queryToken = $request->getQueryParams()['token'] ?? null;

        if (is_string($queryToken) && trim($queryToken) !== '') {
            return trim($queryToken);
        }

        $raw = (string) $request->getBody();

        if ($raw === '') {
            return '';
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded) && isset($decoded['token']) && is_string($decoded['token'])) {
            return trim($decoded['token']);
        }

        return '';
    }
}

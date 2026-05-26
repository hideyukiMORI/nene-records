<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RequestPasswordResetHandler
{
    public function __construct(
        private RequestPasswordResetUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];
        $email = trim((string) ($body['email'] ?? ''));

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email is required.', 'required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ValidationError('email', 'Email format is invalid.', 'format');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $appBaseUrl = (string) ($body['app_base_url'] ?? '');

        if ($appBaseUrl === '') {
            $scheme = $request->getUri()->getScheme();
            $host = $request->getUri()->getHost();
            $port = $request->getUri()->getPort();
            $appBaseUrl = $scheme . '://' . $host . ($port !== null ? ':' . $port : '');
        }

        $this->useCase->execute(new RequestPasswordResetInput(email: $email, appBaseUrl: $appBaseUrl));

        // Always return 204 regardless of whether email exists (prevents enumeration)
        return $this->responseFactory->createResponse(204);
    }
}

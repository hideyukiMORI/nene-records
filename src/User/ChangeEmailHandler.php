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

final readonly class ChangeEmailHandler
{
    public function __construct(
        private ChangeEmailUseCaseInterface $useCase,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id         = (int) ($parameters['id'] ?? 0);

        $body  = JsonRequestBodyParser::parse($request);
        $email = trim((string) ($body['email'] ?? ''));

        $errors = [];

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email address is required.', 'required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ValidationError('email', 'Email address is invalid.', 'invalid_format');
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

        $this->useCase->execute(new ChangeEmailInput(userId: $id, email: $email, appBaseUrl: $appBaseUrl));

        // 202 Accepted: the change is pending until the new address is verified.
        return $this->responseFactory->createResponse(202);
    }
}

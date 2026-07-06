<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use DateTimeImmutable;
use DateTimeInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LoginHandler
{
    public function __construct(
        private LoginUseCase $useCase,
        private JsonResponseFactory $response,
        private ClockInterface $clock,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $email = isset($body['email']) && is_string($body['email']) ? trim($body['email']) : '';
        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email is required.', 'required');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'Password is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new LoginInput(email: $email, password: $password));

        // Set the session token as an HttpOnly cookie so page JS can't read it.
        // The token is still returned in the body for non-browser/machine clients.
        $maxAge = $output->expiresAt - $this->clock->now()->getTimestamp();
        $cookie = SessionCookie::build($output->token, $maxAge, SessionCookie::isSecureRequest($request));

        return $this->response->create([
            'token'          => $output->token,
            'expires_at'     => (new DateTimeImmutable('@' . $output->expiresAt))->format(DateTimeInterface::ATOM),
            'email'          => $output->email,
            'role'           => $output->role,
            'org_id'         => $output->orgId,
            'email_verified' => $output->emailVerified,
        ], 200, ['Set-Cookie' => $cookie]);
    }
}

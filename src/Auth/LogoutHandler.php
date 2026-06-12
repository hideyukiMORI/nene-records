<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Clears the session cookie. Open endpoint — expiring a cookie is harmless and
 * must work even if the current token is already invalid.
 */
final readonly class LogoutHandler
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(204)
            ->withHeader('Set-Cookie', SessionCookie::clear(SessionCookie::isSecureRequest($request)));
    }
}

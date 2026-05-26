<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UserInviteRouteRegistrar
{
    public function __construct(
        private InviteUserHandler $inviteHandler,
        private AcceptInviteHandler $acceptHandler,
        private RequestPasswordResetHandler $requestResetHandler,
        private ConfirmPasswordResetHandler $confirmResetHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $invite = $this->inviteHandler;
        $accept = $this->acceptHandler;
        $requestReset = $this->requestResetHandler;
        $confirmReset = $this->confirmResetHandler;

        $router->post('/api/v1/users/invite', static fn (ServerRequestInterface $r) => $invite->handle($r));
        $router->post('/api/v1/auth/accept-invite', static fn (ServerRequestInterface $r) => $accept->handle($r));
        $router->post('/api/v1/auth/password-reset', static fn (ServerRequestInterface $r) => $requestReset->handle($r));
        $router->post('/api/v1/auth/password-reset/confirm', static fn (ServerRequestInterface $r) => $confirmReset->handle($r));
    }
}

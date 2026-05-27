<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UserRouteRegistrar
{
    public function __construct(
        private ListUsersHandler $listHandler,
        private GetUserByIdHandler $getHandler,
        private CreateUserHandler $createHandler,
        private UpdateUserRoleHandler $updateRoleHandler,
        private ResetUserPasswordHandler $resetPasswordHandler,
        private DeleteUserHandler $deleteHandler,
        private ChangePasswordHandler $changePasswordHandler,
        private ChangeEmailHandler $changeEmailHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $get = $this->getHandler;
        $create = $this->createHandler;
        $updateRole = $this->updateRoleHandler;
        $resetPassword = $this->resetPasswordHandler;
        $delete = $this->deleteHandler;
        $changePassword = $this->changePasswordHandler;
        $changeEmail = $this->changeEmailHandler;

        $router->get('/api/v1/users', static fn (ServerRequestInterface $r) => $list->handle($r));
        $router->get('/api/v1/users/{id}', static fn (ServerRequestInterface $r) => $get->handle($r));
        $router->post('/api/v1/users', static fn (ServerRequestInterface $r) => $create->handle($r));
        $router->patch('/api/v1/users/{id}', static fn (ServerRequestInterface $r) => $updateRole->handle($r));
        $router->patch('/api/v1/users/{id}/email', static fn (ServerRequestInterface $r) => $changeEmail->handle($r));
        $router->patch('/api/v1/users/{id}/password', static fn (ServerRequestInterface $r) => $resetPassword->handle($r));
        $router->delete('/api/v1/users/{id}', static fn (ServerRequestInterface $r) => $delete->handle($r));
        $router->put('/api/v1/users/me/password', static fn (ServerRequestInterface $r) => $changePassword->handle($r));
    }
}

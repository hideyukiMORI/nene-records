<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class NotificationRouteRegistrar
{
    public function __construct(
        private ListNotificationChannelsHandler $listHandler,
        private CreateNotificationChannelHandler $createHandler,
        private UpdateNotificationChannelHandler $updateHandler,
        private DeleteNotificationChannelHandler $deleteHandler,
        private TestNotificationChannelHandler $testHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;
        $test = $this->testHandler;

        $router->get('/api/v1/notification-channels', static fn (ServerRequestInterface $r) => $list->handle($r));
        $router->post('/api/v1/notification-channels', static fn (ServerRequestInterface $r) => $create->handle($r));
        $router->patch('/api/v1/notification-channels/{id}', static fn (ServerRequestInterface $r) => $update->handle($r));
        $router->delete('/api/v1/notification-channels/{id}', static fn (ServerRequestInterface $r) => $delete->handle($r));
        $router->post('/api/v1/notification-channels/{id}/test', static fn (ServerRequestInterface $r) => $test->handle($r));
    }
}

<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListNotificationChannelsHandler
{
    public function __construct(
        private ListNotificationChannelsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        // config capability secrets are write-only on read (#845): the mapper
        // strips them and exposes `has_<key>` flags instead.
        $items = array_map(
            static fn (NotificationChannel $ch): array => NotificationChannelHttpMapper::toArray($ch),
            $output->items,
        );

        return $this->response->create(['items' => $items]);
    }
}

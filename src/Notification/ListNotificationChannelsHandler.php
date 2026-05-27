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

        $items = array_map(
            static fn (NotificationChannel $ch): array => [
                'id'           => $ch->id,
                'channel_type' => $ch->channelType,
                'label'        => $ch->label,
                'is_enabled'   => $ch->isEnabled,
                'config'       => $ch->config,
                'created_at'   => $ch->createdAt,
                'updated_at'   => $ch->updatedAt,
            ],
            $output->items,
        );

        return $this->response->create(['items' => $items]);
    }
}

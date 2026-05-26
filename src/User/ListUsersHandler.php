<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListUsersHandler
{
    public function __construct(
        private ListUsersUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute(new ListUsersInput());

        return $this->response->create([
            'items' => array_map(
                static fn (ListUserItem $item) => [
                    'id' => $item->id,
                    'email' => $item->email,
                    'role' => $item->role,
                    'status' => $item->status,
                    'created_at' => $item->createdAt !== null ? date('c', $item->createdAt) : null,
                    'updated_at' => $item->updatedAt !== null ? date('c', $item->updatedAt) : null,
                ],
                $output->items,
            ),
        ]);
    }
}

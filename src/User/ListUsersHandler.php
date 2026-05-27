<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonResponseFactory;
use NeNeRecords\Auth\Role;
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
        $claims = $request->getAttribute('nene2.auth.claims');
        $role   = is_array($claims) ? Role::tryFrom((string) ($claims['role'] ?? '')) : null;

        // superadmin → 全ユーザー（organizationId = null）
        // admin / editor → JWT の org_id で絞り込む
        $organizationId = null;

        if ($role !== null && $role !== Role::Superadmin) {
            $rawOrgId = $claims['org_id'] ?? null;
            $organizationId = $rawOrgId !== null ? (int) $rawOrgId : null;
        }

        $output = $this->useCase->execute(new ListUsersInput(organizationId: $organizationId));

        return $this->response->create([
            'items' => array_map(
                static fn (ListUserItem $item) => [
                    'id'              => $item->id,
                    'email'           => $item->email,
                    'role'            => $item->role,
                    'organization_id' => $item->organizationId,
                    'org_role'        => $item->orgRole,
                    'status'          => $item->status,
                    'created_at'      => $item->createdAt !== null ? date('c', $item->createdAt) : null,
                    'updated_at'      => $item->updatedAt !== null ? date('c', $item->updatedAt) : null,
                ],
                $output->items,
            ),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace NeNeRecords\Account;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `GET /api/v1/account` — the calling tenant's own account (plan, entitlements,
 * usage). The organization is taken from the resolved request context
 * (`nene2.org.id`), never from a client-supplied id, so a tenant can only ever
 * read its own account.
 */
final readonly class GetAccountHandler
{
    public function __construct(
        private GetAccountUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $organizationId = (int) $request->getAttribute('nene2.org.id');

        $output = $this->useCase->execute($organizationId);

        return $this->response->create([
            'slug'          => $output->slug,
            'name'          => $output->name,
            'plan'          => $output->plan,
            'custom_domain' => $output->customDomain,
            'entitlements'  => [
                'custom_domain_allowed' => $output->customDomainAllowed,
                // null = unlimited (self-host / enterprise); otherwise the cap.
                'max_records'       => self::limit($output->maxRecords),
                'max_storage_bytes' => self::limit($output->maxStorageBytes),
                'max_admin_users'   => self::limit($output->maxAdminUsers),
            ],
            'usage' => [
                'records' => $output->recordsUsed,
            ],
        ]);
    }

    private static function limit(int $value): ?int
    {
        return $value === PHP_INT_MAX ? null : $value;
    }
}

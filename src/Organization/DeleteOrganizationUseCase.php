<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Psr\Log\LoggerInterface;
use Throwable;

final readonly class DeleteOrganizationUseCase implements DeleteOrganizationUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private ?OrgMediaInventoryReaderInterface $mediaInventory = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function execute(DeleteOrganizationInput $input): void
    {
        $org = $this->organizations->findById($input->id);

        if ($org === null) {
            throw new OrganizationNotFoundException($input->id);
        }

        // Record what this delete is about to strand on disk, BEFORE the rows go
        // (#1018). The purge clears `media`, but the files under `var/media` stay;
        // a storage key is `YYYY/MM/<random>.<ext>` and carries no tenant, so after
        // this point nothing can say which org a leftover file belonged to. We only
        // write it down — deleting files here would put an irreversible operation
        // inside a path whose whole point is that it can be rolled back.
        $this->logStrandedMedia($input->id);

        $this->organizations->delete($input->id);
    }

    private function logStrandedMedia(int $organizationId): void
    {
        if ($this->mediaInventory === null || $this->logger === null) {
            return;
        }

        try {
            $inventory = $this->mediaInventory->forOrganization($organizationId);

            if ($inventory->isEmpty()) {
                return;
            }

            $this->logger->info(
                'Organization delete leaves media files on disk; nothing is removed. '
                . 'Run tools/media-orphan-audit.php for the full picture including derivatives.',
                $inventory->toLogContext(),
            );
        } catch (Throwable $exception) {
            // The inventory is a record, not a precondition: failing to take it must
            // never block the delete the operator asked for.
            $this->logger->warning('Failed to inventory media before organization delete.', [
                'organization_id' => $organizationId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

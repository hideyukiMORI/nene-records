<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\Webhook\WebhookDispatcherInterface;

final readonly class CreateEntityUseCase implements CreateEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
        private ?WebhookDispatcherInterface $webhooks = null,
    ) {
    }

    public function execute(CreateEntityInput $input): CreateEntityOutput
    {
        if ($this->entityTypes->findById($input->entityTypeId) === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        $slug = $this->normalizeSlug($input->slug);

        if ($slug !== null && $this->entities->existsBySlug($slug, $input->entityTypeId)) {
            throw new DuplicateEntitySlugException($slug);
        }

        // The handler has already normalized/validated the permalink; treat empty as
        // "no custom permalink". Uniqueness is org-wide (#651), mirroring slug.
        $permalink = ($input->permalink !== null && $input->permalink !== '') ? $input->permalink : null;

        if ($permalink !== null && $this->entities->existsByPermalink($permalink)) {
            throw new DuplicateEntityPermalinkException($permalink);
        }

        $id = $this->entities->save(new Entity(
            id: null,
            entityTypeId: $input->entityTypeId,
            slug: $slug,
            permalink: $permalink,
            status: $input->status,
            layout: $input->layout,
        ));

        $this->webhooks?->dispatch('entity.created', $input->entityTypeId, $id);

        return new CreateEntityOutput(
            id: $id,
            entityTypeId: $input->entityTypeId,
            slug: $slug,
            status: $input->status->value,
            publishedAtIso: null,
            isDeleted: false,
            deletedAtIso: null,
            layout: $input->layout,
            permalink: $permalink,
        );
    }

    private function normalizeSlug(?string $slug): ?string
    {
        if ($slug === null) {
            return null;
        }

        $normalized = trim($slug);

        return $normalized !== '' ? $normalized : null;
    }
}

<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\Webhook\WebhookDispatcherInterface;

final readonly class UpdateEntityUseCase implements UpdateEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
        private ?WebhookDispatcherInterface $webhooks = null,
    ) {
    }

    public function execute(UpdateEntityInput $input): UpdateEntityOutput
    {
        $existing = $this->entities->findById($input->id);

        if ($existing === null) {
            throw new EntityNotFoundException($input->id);
        }

        $entityId = $existing->id;

        if ($entityId === null) {
            throw new LogicException('Loaded entity missing id.');
        }

        if ($this->entityTypes->findById($input->entityTypeId) === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        $slug = $this->normalizeSlug($input->slug);

        if ($slug !== null && $this->entities->existsBySlug($slug, $input->entityTypeId, $entityId)) {
            throw new DuplicateEntitySlugException($slug);
        }

        // Auto-set published_at when transitioning to published for the first time.
        $publishedAt = $input->publishedAt ?? $existing->publishedAt;
        if ($input->status === EntityStatus::Published && $publishedAt === null) {
            $publishedAt = new DateTimeImmutable();
        }

        $updated = new Entity(
            id: $entityId,
            entityTypeId: $input->entityTypeId,
            slug: $slug,
            status: $input->status,
            publishedAt: $publishedAt,
            isDeleted: $existing->isDeleted,
            deletedAt: $existing->deletedAt,
            metaTitle: $input->metaTitle,
            metaDescription: $input->metaDescription,
        );

        $this->entities->update($updated);

        $this->webhooks?->dispatch('entity.updated', $input->entityTypeId, $entityId);

        return new UpdateEntityOutput(
            id: $entityId,
            entityTypeId: $input->entityTypeId,
            slug: $slug,
            status: $input->status->value,
            publishedAtIso: $publishedAt?->format(DateTimeInterface::ATOM),
            isDeleted: $existing->isDeleted,
            deletedAtIso: $existing->deletedAt?->format(DateTimeInterface::ATOM),
            metaTitle: $input->metaTitle,
            metaDescription: $input->metaDescription,
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

<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeInterface;
use LogicException;
use Nene2\Http\ClockInterface;
use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\PublicRecord\PublicPermalinkResolver;
use NeNeRecords\UrlRedirect\UrlRedirectRepositoryInterface;
use NeNeRecords\Webhook\WebhookDispatcherInterface;

final readonly class UpdateEntityUseCase implements UpdateEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
        private ClockInterface $clock,
        private ?WebhookDispatcherInterface $webhooks = null,
        /** Records a 301 from the old canonical path when the permalink changes (#651). */
        private ?UrlRedirectRepositoryInterface $redirects = null,
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

        $entityType = $this->entityTypes->findById($input->entityTypeId);

        if ($entityType === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        $slug = $this->normalizeSlug($input->slug);

        if ($slug !== null && $this->entities->existsBySlug($slug, $input->entityTypeId, $entityId)) {
            throw new DuplicateEntitySlugException($slug);
        }

        // The handler has already normalized/validated the permalink; treat empty as
        // "no custom permalink". Uniqueness is org-wide (#651), excluding self.
        $permalink = ($input->permalink !== null && $input->permalink !== '') ? $input->permalink : null;

        if ($permalink !== null && $this->entities->existsByPermalink($permalink, $entityId)) {
            throw new DuplicateEntityPermalinkException($permalink);
        }

        // Auto-set published_at when transitioning to published for the first time.
        $publishedAt = $input->publishedAt ?? $existing->publishedAt;
        if ($input->status === EntityStatus::Published && $publishedAt === null) {
            $publishedAt = $this->clock->now();
        }

        // scheduled_at is only preserved when status is scheduled; clear otherwise.
        $scheduledAt = $input->status === EntityStatus::Scheduled ? $input->scheduledAt : null;

        $updated = new Entity(
            id: $entityId,
            entityTypeId: $input->entityTypeId,
            slug: $slug,
            permalink: $permalink,
            status: $input->status,
            publishedAt: $publishedAt,
            isDeleted: $existing->isDeleted,
            deletedAt: $existing->deletedAt,
            metaTitle: $input->metaTitle,
            metaDescription: $input->metaDescription,
            scheduledAt: $scheduledAt,
            layout: $input->layout,
        );

        $this->entities->update($updated);

        // When the custom permalink is set, changed, or removed, record a 301 from
        // the record's OLD canonical path to its NEW one, reusing the existing
        // url_redirects mechanism (#651). Set/remove also covers the type-pattern
        // path, so the old URL keeps its SEO equity.
        if ($existing->permalink !== $permalink) {
            $oldCanonical = PublicPermalinkResolver::canonicalPath(
                $existing->permalink,
                $entityType->permalinkPattern,
                $entityType->slug,
                $existing->slug,
                $entityId,
                $existing->publishedAt,
            );
            $newCanonical = PublicPermalinkResolver::canonicalPath(
                $permalink,
                $entityType->permalinkPattern,
                $entityType->slug,
                $slug,
                $entityId,
                $publishedAt,
            );

            if ($oldCanonical !== $newCanonical) {
                $this->redirects?->recordMove($oldCanonical, $newCanonical);
            }
        }

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
            scheduledAtIso: $scheduledAt?->format(DateTimeInterface::ATOM),
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

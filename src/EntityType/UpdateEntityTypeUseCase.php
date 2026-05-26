<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class UpdateEntityTypeUseCase implements UpdateEntityTypeUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(UpdateEntityTypeInput $input): UpdateEntityTypeOutput
    {
        $entityType = $this->entityTypes->findById($input->id);

        if ($entityType === null) {
            throw new EntityTypeNotFoundException($input->id);
        }

        if ($input->slug !== $entityType->slug) {
            $existing = $this->entityTypes->findBySlug($input->slug);

            if ($existing !== null && $existing->id !== $input->id) {
                throw new EntityTypeSlugConflictException($input->slug);
            }
        }

        // When the permalink pattern changes, preserve the old one for redirect purposes.
        $previousPermalinkPattern = $entityType->previousPermalinkPattern;
        if ($input->permalinkPattern !== $entityType->permalinkPattern) {
            $previousPermalinkPattern = $entityType->permalinkPattern;
        }

        $updated = new EntityType(
            name: $input->name,
            slug: $input->slug,
            isPinned: $input->isPinned,
            id: $input->id,
            labels: $input->labels,
            permalinkPattern: $input->permalinkPattern,
            previousPermalinkPattern: $previousPermalinkPattern,
        );
        $this->entityTypes->update($updated);

        return new UpdateEntityTypeOutput(
            id: $input->id,
            name: $input->name,
            slug: $input->slug,
            isPinned: $input->isPinned,
            labels: $input->labels,
            permalinkPattern: $input->permalinkPattern,
            previousPermalinkPattern: $previousPermalinkPattern,
        );
    }
}

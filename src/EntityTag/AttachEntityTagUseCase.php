<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Tag\TagNotFoundException;
use NeNeRecords\Tag\TagRepositoryInterface;

final readonly class AttachEntityTagUseCase implements AttachEntityTagUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private TagRepositoryInterface $tags,
        private EntityTagRepositoryInterface $entityTags,
    ) {
    }

    public function execute(AttachEntityTagInput $input): AttachEntityTagOutput
    {
        if ($this->entities->findById($input->entityId) === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $tag = $this->tags->findById($input->tagId);

        if ($tag === null) {
            throw new TagNotFoundException($input->tagId);
        }

        if ($this->entityTags->isAttached($input->entityId, $input->tagId)) {
            throw new EntityTagAlreadyAttachedException($input->entityId, $input->tagId);
        }

        $this->entityTags->attach($input->entityId, $input->tagId);

        return new AttachEntityTagOutput(
            id: $tag->id ?? $input->tagId,
            slug: $tag->slug,
            name: $tag->name,
        );
    }
}

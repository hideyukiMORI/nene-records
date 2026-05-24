<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class UpdateTagUseCase implements UpdateTagUseCaseInterface
{
    public function __construct(
        private TagRepositoryInterface $tags,
    ) {
    }

    public function execute(UpdateTagInput $input): UpdateTagOutput
    {
        $tag = $this->tags->findById($input->id);

        if ($tag === null) {
            throw new TagNotFoundException($input->id);
        }

        if ($input->slug !== $tag->slug) {
            $existing = $this->tags->findBySlug($input->slug);

            if ($existing !== null && $existing->id !== $input->id) {
                throw new TagSlugConflictException($input->slug);
            }
        }

        $updated = new Tag(slug: $input->slug, name: $input->name, id: $input->id);
        $this->tags->update($updated);

        return new UpdateTagOutput(id: $input->id, slug: $input->slug, name: $input->name);
    }
}

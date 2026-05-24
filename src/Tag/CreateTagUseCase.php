<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class CreateTagUseCase implements CreateTagUseCaseInterface
{
    public function __construct(
        private TagRepositoryInterface $tags,
    ) {
    }

    public function execute(CreateTagInput $input): CreateTagOutput
    {
        $existing = $this->tags->findBySlug($input->slug);

        if ($existing !== null) {
            throw new TagSlugConflictException($input->slug);
        }

        $id = $this->tags->save(new Tag(slug: $input->slug, name: $input->name));

        return new CreateTagOutput(id: $id, slug: $input->slug, name: $input->name);
    }
}

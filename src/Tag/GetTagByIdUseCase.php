<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

final readonly class GetTagByIdUseCase implements GetTagByIdUseCaseInterface
{
    public function __construct(
        private TagRepositoryInterface $tags,
    ) {
    }

    public function execute(GetTagByIdInput $input): GetTagByIdOutput
    {
        $tag = $this->tags->findById($input->id);

        if ($tag === null) {
            throw new TagNotFoundException($input->id);
        }

        return new GetTagByIdOutput(
            id: $tag->id ?? $input->id,
            slug: $tag->slug,
            name: $tag->name,
        );
    }
}

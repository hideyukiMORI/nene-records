<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

use LogicException;

final readonly class ListTagsUseCase implements ListTagsUseCaseInterface
{
    public function __construct(
        private TagRepositoryInterface $tags,
    ) {
    }

    public function execute(ListTagsInput $input): ListTagsOutput
    {
        $rows = $this->tags->findAll($input->limit, $input->offset);

        $items = array_map(static function (Tag $tag): ListTagItem {
            $tagId = $tag->id;

            if ($tagId === null) {
                throw new LogicException('Listed tag missing id.');
            }

            return new ListTagItem(
                id: $tagId,
                slug: $tag->slug,
                name: $tag->name,
            );
        }, $rows);

        return new ListTagsOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}

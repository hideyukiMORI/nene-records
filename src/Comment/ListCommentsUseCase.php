<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ListCommentsUseCase implements ListCommentsUseCaseInterface
{
    public function __construct(private CommentRepositoryInterface $comments)
    {
    }

    public function execute(ListCommentsInput $input): ListCommentsOutput
    {
        $comments = $this->comments->listByEntity($input->entityId, $input->approvedOnly);

        return new ListCommentsOutput(
            items: array_map(ListCommentsItem::fromComment(...), $comments),
        );
    }
}

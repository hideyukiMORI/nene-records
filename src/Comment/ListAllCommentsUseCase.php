<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ListAllCommentsUseCase implements ListAllCommentsUseCaseInterface
{
    public function __construct(private CommentRepositoryInterface $comments)
    {
    }

    public function execute(): ListCommentsOutput
    {
        $comments = $this->comments->listAll();

        return new ListCommentsOutput(
            items: array_map(ListCommentsItem::fromComment(...), $comments),
        );
    }
}

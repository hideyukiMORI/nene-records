<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class PostCommentUseCase implements PostCommentUseCaseInterface
{
    public function __construct(private CommentRepositoryInterface $comments)
    {
    }

    public function execute(PostCommentInput $input): Comment
    {
        return $this->comments->create(
            $input->entityId,
            $input->authorName,
            $input->authorEmail,
            $input->body,
        );
    }
}

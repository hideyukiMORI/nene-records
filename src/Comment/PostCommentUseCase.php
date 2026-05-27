<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class PostCommentUseCase implements PostCommentUseCaseInterface
{
    public function __construct(private CommentRepositoryInterface $comments)
    {
    }

    public function execute(PostCommentInput $input): PostCommentOutput
    {
        $comment = $this->comments->create(
            $input->entityId,
            $input->authorName,
            $input->authorEmail,
            $input->body,
        );

        return new PostCommentOutput(
            id: $comment->id,
            entityId: $comment->entityId,
            authorName: $comment->authorName,
            body: $comment->body,
            isApproved: $comment->isApproved,
            createdAt: $comment->createdAt,
        );
    }
}

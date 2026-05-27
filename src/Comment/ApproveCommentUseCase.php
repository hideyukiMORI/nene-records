<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ApproveCommentUseCase implements ApproveCommentUseCaseInterface
{
    public function __construct(private CommentRepositoryInterface $comments)
    {
    }

    public function execute(ApproveCommentInput $input): ApproveCommentOutput
    {
        $comment = $this->comments->approve($input->id);

        return new ApproveCommentOutput(
            id: $comment->id,
            entityId: $comment->entityId,
            authorName: $comment->authorName,
            body: $comment->body,
            isApproved: $comment->isApproved,
            createdAt: $comment->createdAt,
        );
    }
}

<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class ApproveCommentUseCase implements ApproveCommentUseCaseInterface
{
    public function __construct(private CommentRepositoryInterface $comments)
    {
    }

    public function execute(ApproveCommentInput $input): Comment
    {
        return $this->comments->approve($input->id);
    }
}

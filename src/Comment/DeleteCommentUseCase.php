<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

final readonly class DeleteCommentUseCase implements DeleteCommentUseCaseInterface
{
    public function __construct(private CommentRepositoryInterface $comments)
    {
    }

    public function execute(DeleteCommentInput $input): void
    {
        $this->comments->delete($input->id);
    }
}

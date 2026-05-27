<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use NeNeRecords\Notification\NotificationMessage;
use NeNeRecords\Notification\NotifierInterface;

final readonly class PostCommentUseCase implements PostCommentUseCaseInterface
{
    public function __construct(
        private CommentRepositoryInterface $comments,
        private NotifierInterface $notifier,
    ) {
    }

    public function execute(PostCommentInput $input): PostCommentOutput
    {
        $comment = $this->comments->create(
            $input->entityId,
            $input->authorName,
            $input->authorEmail,
            $input->body,
        );

        $this->notifier->notify(new NotificationMessage(
            event: 'comment.submitted',
            title: '新規コメントが届きました',
            body: "{$comment->authorName} さんからコメントが届きました:\n{$comment->body}",
        ));

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

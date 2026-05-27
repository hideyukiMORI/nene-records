<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Comment;

use NeNeRecords\Comment\ApproveCommentInput;
use NeNeRecords\Comment\ApproveCommentUseCase;
use NeNeRecords\Comment\DeleteCommentInput;
use NeNeRecords\Comment\DeleteCommentUseCase;
use NeNeRecords\Comment\PostCommentInput;
use NeNeRecords\Comment\PostCommentUseCase;
use NeNeRecords\Notification\NullNotifier;
use PHPUnit\Framework\TestCase;

final class CommentUseCaseTest extends TestCase
{
    // ── PostCommentUseCase ────────────────────────────────────────────────

    public function testPostCommentCreatesCommentWithIsApprovedFalse(): void
    {
        $comments = new InMemoryCommentRepository();
        $useCase = new PostCommentUseCase($comments, new NullNotifier());

        $output = $useCase->execute(new PostCommentInput(
            entityId: 1,
            authorName: 'Alice',
            authorEmail: 'alice@example.com',
            body: 'Great post!',
        ));

        self::assertSame(false, $output->isApproved);
    }

    public function testPostCommentReturnsCorrectOutput(): void
    {
        $comments = new InMemoryCommentRepository();
        $useCase = new PostCommentUseCase($comments, new NullNotifier());

        $output = $useCase->execute(new PostCommentInput(
            entityId: 42,
            authorName: 'Bob',
            authorEmail: 'bob@example.com',
            body: 'Interesting article.',
        ));

        self::assertSame(1, $output->id);
        self::assertSame(42, $output->entityId);
        self::assertSame('Bob', $output->authorName);
        self::assertSame('Interesting article.', $output->body);
        self::assertSame(false, $output->isApproved);
    }

    public function testPostCommentAssignsSequentialIds(): void
    {
        $comments = new InMemoryCommentRepository();
        $useCase = new PostCommentUseCase($comments, new NullNotifier());

        $first = $useCase->execute(new PostCommentInput(
            entityId: 1,
            authorName: 'Alice',
            authorEmail: 'alice@example.com',
            body: 'First comment',
        ));
        $second = $useCase->execute(new PostCommentInput(
            entityId: 1,
            authorName: 'Bob',
            authorEmail: 'bob@example.com',
            body: 'Second comment',
        ));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    // ── ApproveCommentUseCase ─────────────────────────────────────────────

    public function testApproveCommentSetsIsApprovedTrue(): void
    {
        $comments = new InMemoryCommentRepository();
        $posted = (new PostCommentUseCase($comments, new NullNotifier()))->execute(new PostCommentInput(
            entityId: 1,
            authorName: 'Alice',
            authorEmail: 'alice@example.com',
            body: 'Nice work!',
        ));

        self::assertSame(false, $posted->isApproved);

        $output = (new ApproveCommentUseCase($comments))->execute(
            new ApproveCommentInput(id: $posted->id),
        );

        self::assertSame(true, $output->isApproved);
    }

    public function testApproveCommentReturnsCorrectOutput(): void
    {
        $comments = new InMemoryCommentRepository();
        $posted = (new PostCommentUseCase($comments, new NullNotifier()))->execute(new PostCommentInput(
            entityId: 7,
            authorName: 'Carol',
            authorEmail: 'carol@example.com',
            body: 'Wonderful!',
        ));

        $output = (new ApproveCommentUseCase($comments))->execute(
            new ApproveCommentInput(id: $posted->id),
        );

        self::assertSame($posted->id, $output->id);
        self::assertSame(7, $output->entityId);
        self::assertSame('Carol', $output->authorName);
        self::assertSame('Wonderful!', $output->body);
        self::assertSame(true, $output->isApproved);
    }

    // ── DeleteCommentUseCase ──────────────────────────────────────────────

    public function testDeleteCommentSuccessfully(): void
    {
        $comments = new InMemoryCommentRepository();
        $posted = (new PostCommentUseCase($comments, new NullNotifier()))->execute(new PostCommentInput(
            entityId: 1,
            authorName: 'Dave',
            authorEmail: 'dave@example.com',
            body: 'To be deleted',
        ));

        (new DeleteCommentUseCase($comments))->execute(
            new DeleteCommentInput(id: $posted->id),
        );

        $remaining = $comments->listByEntity(1, false);
        self::assertSame([], $remaining);
    }

    public function testDeleteCommentDeletesOnlySpecifiedComment(): void
    {
        $comments = new InMemoryCommentRepository();
        $postUseCase = new PostCommentUseCase($comments, new NullNotifier());

        $first = $postUseCase->execute(new PostCommentInput(
            entityId: 1,
            authorName: 'Alice',
            authorEmail: 'alice@example.com',
            body: 'Keep me',
        ));
        $second = $postUseCase->execute(new PostCommentInput(
            entityId: 1,
            authorName: 'Bob',
            authorEmail: 'bob@example.com',
            body: 'Delete me',
        ));

        (new DeleteCommentUseCase($comments))->execute(
            new DeleteCommentInput(id: $second->id),
        );

        $remaining = $comments->listByEntity(1, false);
        self::assertSame(1, count($remaining));
        self::assertSame($first->id, $remaining[0]->id);
    }
}

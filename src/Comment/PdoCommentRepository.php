<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\RequestScopedHolder;

final readonly class PdoCommentRepository implements CommentRepositoryInterface
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private readonly RequestScopedHolder $orgId,
    ) {
    }

    public function listByEntity(int $entityId, bool $approvedOnly): array
    {
        $sql = 'SELECT * FROM comments WHERE entity_id = :entity_id AND organization_id = :organization_id';
        if ($approvedOnly) {
            $sql .= ' AND is_approved = 1';
        }
        $sql .= ' ORDER BY created_at ASC';

        $rows = $this->query->fetchAll($sql, [':entity_id' => $entityId, ':organization_id' => $this->orgId->get()]);

        return array_map($this->hydrate(...), $rows);
    }

    public function listAll(): array
    {
        $rows = $this->query->fetchAll('SELECT * FROM comments WHERE organization_id = :organization_id ORDER BY created_at DESC', [':organization_id' => $this->orgId->get()]);

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(int $id): Comment
    {
        $row = $this->query->fetchOne('SELECT * FROM comments WHERE id = :id AND organization_id = :organization_id', [':id' => $id, ':organization_id' => $this->orgId->get()]);

        if ($row === null) {
            throw new CommentNotFoundException($id);
        }

        return $this->hydrate($row);
    }

    public function create(int $entityId, string $authorName, string $authorEmail, string $body): Comment
    {
        $id = $this->query->insert(
            'INSERT INTO comments (organization_id, entity_id, author_name, author_email, body, is_approved, created_at)
             VALUES (:organization_id, :entity_id, :author_name, :author_email, :body, 0, NOW())',
            [
                ':organization_id' => $this->orgId->get(),
                ':entity_id'       => $entityId,
                ':author_name'     => $authorName,
                ':author_email'    => $authorEmail,
                ':body'            => $body,
            ],
        );

        return $this->findById($id);
    }

    public function approve(int $id): Comment
    {
        $this->findById($id); // throws if not found

        $this->query->execute(
            'UPDATE comments SET is_approved = 1 WHERE id = :id AND organization_id = :organization_id',
            [':id' => $id, ':organization_id' => $this->orgId->get()],
        );

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->findById($id); // throws if not found

        $this->query->execute('DELETE FROM comments WHERE id = :id AND organization_id = :organization_id', [':id' => $id, ':organization_id' => $this->orgId->get()]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Comment
    {
        return new Comment(
            id: (int) $row['id'],
            entityId: (int) $row['entity_id'],
            authorName: (string) $row['author_name'],
            authorEmail: (string) $row['author_email'],
            body: (string) $row['body'],
            isApproved: (bool) $row['is_approved'],
            createdAt: (string) $row['created_at'],
        );
    }
}
